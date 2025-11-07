import os
from pymongo import MongoClient
from dotenv import load_dotenv

load_dotenv()

client = None
db = None

def init_db():
    """Inizializza la connessione a MongoDB"""
    global client, db
    try:
        client = MongoClient("mongodb://127.0.0.1:27017")
        db = client["xrpl_quiz"]  # Nome coerente del DB
        print("✅ Connected to MongoDB")
    except Exception as e:
        print("❌ MongoDB connection failed:", e)

def get_db():
    """Ritorna l’oggetto del database"""
    global db
    if db is None:
        init_db()
    return db
