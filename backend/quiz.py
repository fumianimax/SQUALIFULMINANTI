# backend/quiz.py
from fastapi import APIRouter, HTTPException, Depends
from backend.dependencies import get_current_user  # <-- AGGIUNGI
from backend.database import quiz_col, answers_col, users_col
from backend.xrpl_utils import send_quiz_proof
import time
import random

router = APIRouter(tags=["quiz"])

QUIZ_DURATION = 300 # in secondi
TIME_PER_QUESTION = 120 # in secondi
REWARD_AMOUNT = 10 # in drops

BASE_QUIZ = [
    {"id": 1, "question": "Qual è il capitale della Francia?", "options": ["Roma","Parigi","Berlino","Madrid"], "answer": "Parigi"},
    {"id": 2, "question": "Chi ha scritto '1984'?", "options": ["Orwell","Dante","Hemingway","Kafka"], "answer": "Orwell"},
    {"id": 3, "question": "XRPL è stato creato da?", "options": ["Satoshi", "Vitalik", "Ripple Labs", "Binance"], "answer": "Ripple Labs"},
    {"id": 4, "question": "Il token nativo di XRPL è?", "options": ["BTC", "ETH", "XRP", "ADA"], "answer": "XRP"},
    {"id": 5, "question": "Qual è il capitale della Francia?", "options": ["Roma","Parigi","Berlino","Madrid"], "answer": "Parigi"},
    {"id": 6, "question": "Chi ha scritto '1984'?", "options": ["Orwell","Dante","Hemingway","Kafka"], "answer": "Orwell"},
    {"id": 7, "question": "XRPL è stato creato da?", "options": ["Satoshi", "Vitalik", "Ripple Labs", "Binance"], "answer": "Ripple Labs"},
    {"id": 8, "question": "Il token nativo di XRPL è?", "options": ["BTC", "ETH", "XRP", "ADA"], "answer": "XRP"},
    {"id": 9, "question": "Qual è il capitale della Francia?", "options": ["Roma","Parigi","Berlino","Madrid"], "answer": "Parigi"},
    {"id": 10, "question": "Chi ha scritto '1984'?", "options": ["Orwell","Dante","Hemingway","Kafka"], "answer": "Orwell"},
]

@router.get("/start")
async def start_quiz(current_user: dict = Depends(get_current_user)):
    username = current_user["username"]
    start_time = time.time()

    shuffled = random.sample(BASE_QUIZ, len(BASE_QUIZ))
    quiz_with_options = [
        {"id": q["id"], "question": q["question"], "options": q["options"]}
        for q in shuffled
    ]
    
    # FIX: converti chiavi in stringhe
    answer_map = {str(q["id"]): q["answer"] for q in shuffled}

    session = {
        "username": username,
        "start_time": start_time,
        "quiz_id": int(start_time),
        "answer_map": answer_map,  # <-- ora OK
        "shuffled_questions": [q["id"] for q in shuffled]
    }
    quiz_col.insert_one(session)

    return {
        "quiz": quiz_with_options,
        "quiz_id": session["quiz_id"],
        "start_time": start_time,
        "duration": QUIZ_DURATION,
        "time_per_question": TIME_PER_QUESTION
    }

@router.post("/answer")
async def submit_answer(data: dict, current_user: dict = Depends(get_current_user)):
    username = current_user["username"]
    question_id = data["question_id"]  # ← questo è int
    choice = data["choice"]
    quiz_id = data["quiz_id"]

    session = quiz_col.find_one({"username": username, "quiz_id": quiz_id})
    if not session:
        raise HTTPException(404, "Sessione non trovata")

    # FIX: converti question_id in stringa
    correct_answer = session["answer_map"].get(str(question_id))
    if not correct_answer:
        raise HTTPException(404, "Domanda non valida")

    correct = (choice == correct_answer) and (choice != "NESSUNA_RISPOSTA")

    answers_col.insert_one({
        "username": username,
        "quiz_id": quiz_id,
        "question_id": question_id,
        "choice": choice,
        "correct": correct,
        "timestamp": time.time()
    })

    return {"correct": correct}

@router.post("/submit")
async def submit_quiz(data: dict, current_user: dict = Depends(get_current_user)):
    username = current_user["username"]
    quiz_id = data["quiz_id"]

    session = quiz_col.find_one({"username": username, "quiz_id": quiz_id})
    if not session:
        raise HTTPException(404, "Sessione non trovata")

    # RIMUOVI QUESTO CONTROLLO
    # elapsed = time.time() - session["start_time"]
    # if elapsed > QUIZ_DURATION:
    #     raise HTTPException(400, "Tempo scaduto!")

    user_answers = list(answers_col.find({"username": username, "quiz_id": quiz_id}))
    
    # Se non ci sono risposte → tutte false
    if not user_answers:
        score = 0
        all_correct = False
    else:
        all_correct = all(a["correct"] for a in user_answers)
        score = round((sum(a["correct"] for a in user_answers) / len(BASE_QUIZ)) * 100, 2)

    user = users_col.find_one({"username": username})
    wallet_seed = user["seed"]

    tx_hash = None
    if all_correct and len(user_answers) == len(BASE_QUIZ):
        try:
            tx_hash = await send_quiz_proof(wallet_seed, {
                "username": username,
                "score": score,
                "timestamp": time.time()
            })
        except Exception as e:
            raise HTTPException(500, f"Errore XRPL: {e}")

    quiz_col.update_one(
        {"username": username, "quiz_id": quiz_id},
        {"$set": {
            "score": score,
            "tx_hash": tx_hash,
            "completed": True,
            "completed_at": time.time()
        }}
    )

    return {
        "msg": "Quiz completato!",
        "score": score,
        "all_correct": all_correct,
        "tx_hash": tx_hash
    }