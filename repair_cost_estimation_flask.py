from flask import Flask, request, jsonify
import faiss
import numpy as np
import pandas as pd
from sentence_transformers import SentenceTransformer
import re

app = Flask(__name__)

# Load the dataset
file_path = "Raw dataset.csv"
df = pd.read_csv(file_path)

# Select only relevant columns (ignoring URLs)
df["combined_text"] = df["car_parts_name"] + " - " + df["car_parts_parts_price"]
car_parts = df["combined_text"].tolist()

# Load the embedding model
embed_model = SentenceTransformer("all-MiniLM-L6-v2")

# Convert text data to embeddings
embeddings = embed_model.encode(car_parts)
embeddings = np.array(embeddings, dtype=np.float32)

# Create FAISS index
index = faiss.IndexFlatL2(embeddings.shape[1])
index.add(embeddings)

def extract_price(text):
    """Extracts numerical price from a text string, ensuring it picks the correct PKR value."""
    match = re.search(r"PKR\s*([\d,]+)", text)
    if match:
        price = match.group(1).replace(",", "")  # Remove commas
        return float(price) if price.isdigit() else None
    return None
def retrieve_damaged_parts(claim_details_list, top_k=20):
    """Retrieve relevant damaged car parts based on multiple insurance claim details."""
    total_cost = 0.0
    all_retrieved_parts = []
    all_missing_prices = []
    
    # Dictionary to keep track of highest cost parts by claim and part type
    highest_cost_parts = {}
    
    for claim_details in claim_details_list:
        query_embedding = embed_model.encode([claim_details])
        query_embedding = np.array(query_embedding, dtype=np.float32)
        distances, indices = index.search(query_embedding, top_k)
        
        # Get candidate results
        candidate_results = [(car_parts[i], distances[0][j]) for j, i in enumerate(indices[0])]
        found_match = False
        
        # Preprocess claim details for better matching
        claim_words = [word.lower() for word in claim_details.split()]
        
        # Extract important attributes from the claim
        claim_position = None
        claim_part_type = None
        claim_make = None
        claim_model = None
        claim_year = None
        
        # Look for position indicators (front, rear, side, etc.)
        position_keywords = ["front", "rear", "back", "side", "left", "right"]
        for word in claim_words:
            if word.lower() in position_keywords:
                claim_position = word.lower()
                break
                
        # Look for part types (bumper, light, door, etc.)
        part_keywords = ["bumper", "light", "door", "mirror", "hood", "trunk", "window", "cover"]
        for word in claim_words:
            if word.lower() in part_keywords:
                claim_part_type = word.lower()
                break
                
        # Look for common car makes
        for word in claim_words:
            if word.lower() in ["honda", "toyota", "suzuki", "nissan", "hyundai", "kia"]:
                claim_make = word.lower()
                break
                
        # Look for common car models
        for word in claim_words:
            if word.lower() in ["civic", "corolla", "city", "alto", "mehran", "cultus"]:
                claim_model = word.lower()
                break
                
        # Look for years
        for word in claim_words:
            if word.isdigit() and len(word) == 4:
                claim_year = int(word)
                break
        
        # Create a key for this claim type
        if claim_position and claim_part_type:
            claim_type_key = f"{claim_position}_{claim_part_type}"
        else:
            claim_type_key = claim_details  # Fallback if we can't extract specific position/part
        
        matching_parts_with_prices = []
        
        for part, distance in candidate_results:
            part_lower = part.lower()
            
            # Ensure critical attributes match
            # 1. Position must match exactly (front/rear/etc.)
            position_match = True
            if claim_position:
                opposite_positions = {"front": ["rear", "back"], "rear": ["front"], "back": ["front"], 
                                    "left": ["right", "rh"], "right": ["left", "lh"], 
                                    "lh": ["right", "rh"], "rh": ["left", "lh"]}
                
                # Exclude parts with opposite position
                if any(pos in part_lower for pos in opposite_positions.get(claim_position, [])):
                    position_match = False
                    
                # Ensure the claimed position is in the part description
                if claim_position not in part_lower:
                    position_match = False
            
            # 2. Part type must match
            part_type_match = True
            if claim_part_type and claim_part_type not in part_lower:
                part_type_match = False
                
            # 3. Make and model must match
            make_model_match = True
            if claim_make and claim_make not in part_lower:
                make_model_match = False
            if claim_model and claim_model not in part_lower:
                make_model_match = False
            
            # 4. Year must be in range if specified
            year_match = True
            if claim_year:
                year_range_match = re.search(r'(\d{4})-(\d{4})', part)
                if year_range_match:
                    start_year = int(year_range_match.group(1))
                    end_year = int(year_range_match.group(2))
                    year_match = start_year <= claim_year <= end_year
                elif str(claim_year) not in part:
                    # If no range is found, look for exact year match
                    year_match = False
            
            # Count matching words for general similarity
            matching_words = sum(1 for word in claim_words if word in part_lower)
            match_ratio = matching_words / len(claim_words)
            
            # Accept part if it passes all specific checks and has good general similarity
            if (position_match and part_type_match and make_model_match and year_match and 
                match_ratio >= 0.5 and distance < 1.8):
                price = extract_price(part)
                if price is not None:
                    matching_parts_with_prices.append((part, price))
                    found_match = True
                else:
                    all_missing_prices.append(part)
        
        # Add all matching parts to the results
        if matching_parts_with_prices:
            # Sort by price (descending)
            matching_parts_with_prices.sort(key=lambda x: x[1], reverse=True)
            all_retrieved_parts.extend([part for part, _ in matching_parts_with_prices])
            
            # Store the highest price part for this claim type
            highest_cost_parts[claim_type_key] = matching_parts_with_prices[0]
        else:
            all_retrieved_parts.append(f"No matching parts found for: {claim_details}")
    
    # Calculate total cost using only the highest price for each claim type
    for claim_type, (part, price) in highest_cost_parts.items():
        total_cost += price
    
    return all_retrieved_parts, total_cost, all_missing_prices
@app.route("/get_parts_cost", methods=["POST"])
def get_parts_cost():
    data = request.json
    claim_details_list = data.get("damaged_parts", [])
    retrieved_parts, total_price, missing_prices = retrieve_damaged_parts(claim_details_list)
    
    response = {
        "claimed_parts": retrieved_parts,
        "total_cost": f"PKR {total_price:,.2f}",
        "missing_prices": missing_prices
    }
    return jsonify(response)

if __name__ == "__main__":
    app.run(debug=True)
