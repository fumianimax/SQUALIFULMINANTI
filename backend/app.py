from flask import Flask, jsonify, request
from flask_cors import CORS
from utils.db import init_db, get_db
from werkzeug.security import generate_password_hash, check_password_hash
import jwt, datetime, os

app = Flask(__name__)
CORS(app)
app.config['SECRET_KEY'] = 'supersecret'  # o meglio prendi da .env

# Inizializza DB
init_db()

@app.route("/")
def home():
    return jsonify({"message": "XRPL Quiz API is running!"})

@app.route("/register", methods=["POST"])
def register():
    db = get_db()
    data = request.get_json()
    username = data.get("username")
    password = data.get("password")

    if not username or not password:
        return jsonify({"success": False, "message": "Dati mancanti"}), 400

    if db.users.find_one({"username": username}):
        return jsonify({"success": False, "message": "Utente già esistente"}), 400

    hashed_pw = generate_password_hash(password)
    db.users.insert_one({"username": username, "password": hashed_pw, "score": 0})
    return jsonify({"success": True, "message": "Registrazione completata!"})

@app.route("/login", methods=["POST"])
def login():
    db = get_db()
    data = request.get_json()
    username = data.get("username")
    password = data.get("password")

    user = db.users.find_one({"username": username})
    if not user or not check_password_hash(user["password"], password):
        return jsonify({"success": False, "message": "Credenziali non valide"}), 401

    # Genera token JWT
    token = jwt.encode({
        "username": username,
        "exp": datetime.datetime.utcnow() + datetime.timedelta(hours=2)
    }, app.config["SECRET_KEY"], algorithm="HS256")

    return jsonify({
        "success": True,
        "user": {"username": username, "score": user.get("score", 0)},
        "token": token
    })

if __name__ == "__main__":
    app.run(port=5001, debug=True)
