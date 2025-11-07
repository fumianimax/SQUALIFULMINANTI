from flask import Blueprint, request, jsonify
from utils.db import get_db

user_bp = Blueprint("user_bp", __name__)

@user_bp.route("/register", methods=["POST"])
def register_user():
    db = get_db()
    data = request.get_json()
    username = data.get("username")
    password = data.get("password")

    if db.users.find_one({"username": username}):
        return jsonify({"success": False, "message": "Utente già registrato."})

    db.users.insert_one({"username": username, "password": password, "score": 0})
    return jsonify({"success": True, "message": "Registrazione completata."})


@user_bp.route("/login", methods=["POST"])
def login_user():
    db = get_db()
    data = request.get_json()
    username = data.get("username")
    password = data.get("password")

    user = db.users.find_one({"username": username, "password": password})
    if user:
        return jsonify({
            "success": True,
            "message": "Login effettuato con successo.",
            "user": {"username": user["username"], "score": user.get("score", 0)}
        })
    else:
        return jsonify({"success": False, "message": "Credenziali errate."})
