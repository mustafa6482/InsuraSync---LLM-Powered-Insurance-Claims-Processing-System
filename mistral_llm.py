from flask import Flask, request, jsonify
import ollama

app = Flask(__name__)

def check_claim_coverage(accident_description, policy_text):
    prompt = f""" You are an expert in insurance policy analysis.
    
    Given the following insurance policy:
    "{policy_text}"
    
    And the following accident description:
    "{accident_description}"
    
    Determine whether the claim is:
    - "Covered" (if the policy explicitly covers the accident)
    - "Not Covered" (if the policy explicitly excludes or does not cover it)
    - "Unclear" (if the policy lacks enough details to make a definitive decision)
    
    Provide the decision as one of the three options: **Covered**, **Not Covered**, or **Unclear**, followed by a brief explanation.
    """
    
    response = ollama.chat(model="mistral", messages=[{"role": "user", "content": prompt}])
    return response["message"]["content"]

@app.route('/check_claim', methods=['POST'])
def check_claim():
    data = request.get_json()
    accident_description = data.get("accident_description")
    policy_text = data.get("policy_text")
    
    if not accident_description or not policy_text:
        return jsonify({"error": "Both accident_description and policy_text are required."}), 400
    
    result = check_claim_coverage(accident_description, policy_text)
    return jsonify({"result": result})

# Keep the existing parts cost analysis endpoint
@app.route('/get_parts_cost', methods=['POST'])
def get_parts_cost():
    data = request.get_json()
    damaged_parts = data.get("damaged_parts", [])
    
    # This is where your existing parts cost logic would go
    # Just creating a placeholder response for now
    result = {
        "claimed_parts": [f"{part}: $500" for part in damaged_parts],
        "total_cost": f"${500 * len(damaged_parts)}",
        "missing_prices": []
    }
    
    return jsonify(result)

if __name__ == '__main__':
    app.run(debug=True, port=5001)