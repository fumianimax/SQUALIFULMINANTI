from flask import Blueprint, jsonify, request
from utils.db import db

quiz_bp = Blueprint("quiz", __name__)

# Quiz di esempio
QUIZ = [
    {"_id": 1, "question": "Cos'è l'XRPL?", "options": ["Un ledger decentralizzato", "Un social network", "Una banca"], "answer": "Un ledger decentralizzato"},
    {"_id": 2, "question": "Qual è la valuta nativa?", "options": ["BTC", "ETH", "XRP"], "answer": "XRP"},
    {"_id": 3, "question": "Chi ha creato XRPL?", "options": ["Ripple Labs", "OpenAI", "Google"], "answer": "Ripple Labs"},
]

@quiz_bp.route("/", methods=["GET"])
def get_quiz():
    return jsonify(QUIZ)

@quiz_bp.route("/answer", methods=["POST"])
def check_answer():
    data = request.json
    username = data.get("username")
    quiz_id = data.get("quiz_id")
    answer = data.get("answer")

    question = next((q for q in QUIZ if q["_id"] == quiz_id), None)
    if not question:
        return jsonify({"error": "Quiz not found"}), 404

    correct = (answer == question["answer"])
    if correct:
        db.users.update_one({"username": username}, {"$inc": {"points": 10}})

    return jsonify({"correct": correct, "message": "Correct!" if correct else "Wrong!"})
