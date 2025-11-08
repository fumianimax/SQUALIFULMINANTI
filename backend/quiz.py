from fastapi import APIRouter, HTTPException, Depends
from backend.dependencies import get_current_user
from backend.database import quiz_col, answers_col, users_col
from backend.xrpl_utils import send_quiz_proof, send_prize
import time
import random
import os
import asyncio
from xrpl.clients import JsonRpcClient
from xrpl.wallet import Wallet
from xrpl.models.transactions import Payment
from xrpl.models.requests import AccountInfo
from xrpl.transaction import submit_and_wait
from xrpl.utils import xrp_to_drops
import logging

router = APIRouter(tags=["quiz"])

QUIZ_DURATION = 300
TIME_PER_QUESTION = 2
REWARD_AMOUNT = 10  # in drops

BASE_QUIZ = [
    {"id": 1, "question": "Who wrote the novel “1984”?", "options": ["George Orwell","Oscar Wilde","Josè Seramago","Luigi Pirandello"], "answer": "George Orwell"},
    {"id": 2, "question": "What is the largest desert in the world (by area)?", "options": ["Sahara Desert","Antarctic Desert","Chihuahuan desert","Polar Desert"], "answer": "Antarctic Desert"},
    {"id": 3, "question": "What planet is known as the “Red Planet”?", "options": ["Mars", "Jupiter", "Venus", "Uranus"], "answer": "Mars"},
    {"id": 4, "question": "Which ancient civilisation built the Machu Picchu complex in Peru?", "options": ["Aztecs", "Mayans", "Incas", "Olmecs"], "answer": "Incas"},
    {"id": 5, "question": "Which of these instruments is not part of the brass family?", "options": ["Saxophone","Trumpet","Contrabass tuba","Horn"], "answer": "Saxophone"},
    {"id": 6, "question": "What is the nationality of most of Paul Gauguin’s models?", "options": ["Vietnamese","French","Polynesian","Canadian"], "answer": "Polynesian"},
    {"id": 7, "question": "In which italian city did the event 'Hackathon', focused on XRPL and cryptography, take place in 2025?", "options": ["Rome", "Turin", "Milan", "Florence"], "answer": "Rome"},
    {"id": 8, "question": "In which fields do you think blockchains could be used the most?", "options": ["Discrete Manufacturing ", "Banking, Financial Services and Insurance", "Family farms and small agricultural businesses", "Car braking systems"], "answer": "Banking, Financial Services and Insurance"},
    {"id": 9, "question": "If a bank wanted to use blockchain for interbank payments, which type would be the most suitable?", "options": ["Public blockchain","Private blockchain","Consortium blockchain","Hybrid blockchain"], "answer": "Consortium blockchain"},
    {"id": 10, "question": "Which of the following is not a blockchain node?", "options": ["full node","selector node","light node","mining node"], "answer": "selector node"},
]

# --- XRPL CLIENT ---
client = JsonRpcClient("https://s.altnet.rippletest.net:51234")

# --- WALLET SERVER (SICURO) ---
SERVER_SEED = os.getenv("XRPL_SEED")  # ← .env
if not SERVER_SEED:
    raise RuntimeError("XRPL_SEED non impostato in .env")


# --- GET BALANCE ---
@router.get("/balance")
async def get_balance(current_user: dict = Depends(get_current_user)):
    username = current_user["username"]
    user = users_col.find_one({"username": username})
    if not user or "xrpl_address" not in user:
        raise HTTPException(400, "Indirizzo XRPL non trovato")

    address = user["xrpl_address"]
    client = JsonRpcClient("https://s.altnet.rippletest.net:51234")

    max_attempts = 12
    for attempt in range(max_attempts):
        try:
            # ESEGUI IN THREAD SEPARATO
            info = await asyncio.to_thread(
                client.request,
                AccountInfo(
                    account=address,
                    ledger_index="validated",
                    strict=True
                )
            )

            balance_drops = int(info.result["account_data"]["Balance"])
            balance_xrp = balance_drops / 1_000_000
            reserve = 10.0
            available = max(0, balance_xrp - reserve)

            return {
                "balance": round(balance_xrp, 6),
                "available": round(available, 6),
                "address": address,
                "reserve": reserve,
                "source": "xrpl_testnet",
                "activated": True
            }

        except Exception as e:
            error_msg = str(e).lower()
            if "account not found" in error_msg or "actnotfound" in error_msg:
                if attempt < max_attempts - 1:
                    await asyncio.sleep(5)
                    continue
                else:
                    return {
                        "balance": 0.0,
                        "available": 0.0,
                        "address": address,
                        "reserve": 10.0,
                        "source": "xrpl_testnet",
                        "activated": False,
                        "error": "Account non ancora attivo. Riprova tra 30 secondi."
                    }
            else:
                raise HTTPException(500, f"Errore XRPL: {str(e)}")

# --- START QUIZ ---
@router.get("/start")
async def start_quiz(current_user: dict = Depends(get_current_user)):
    username = current_user["username"]
    start_time = time.time()

    shuffled = random.sample(BASE_QUIZ, len(BASE_QUIZ))
    quiz_with_options = [
        {"id": q["id"], "question": q["question"], "options": q["options"]}
        for q in shuffled
    ]
    
    answer_map = {str(q["id"]): q["answer"] for q in shuffled}

    session = {
        "username": username,
        "start_time": start_time,
        "quiz_id": int(start_time),
        "answer_map": answer_map,
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

# --- SUBMIT ANSWER ---
@router.post("/answer")
async def submit_answer(data: dict, current_user: dict = Depends(get_current_user)):
    username = current_user["username"]
    question_id = data["question_id"]
    choice = data["choice"]
    quiz_id = data["quiz_id"]

    session = quiz_col.find_one({"username": username, "quiz_id": quiz_id})
    if not session:
        raise HTTPException(404, "Sessione non trovata")

    correct_answer = session["answer_map"].get(str(question_id))
    if not correct_answer:
        raise HTTPException(404, "Domanda non valida")

    already = answers_col.find_one({
        "username": username,
        "quiz_id": quiz_id,
        "question_id": question_id
    })
    if already:
        return {"correct": already["correct"], "message": "Già risposto"}

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
    
# --- SUBMIT QUIZ (LOGICA COMPLETA) ---
@router.post("/submit")
async def submit_quiz(data: dict, current_user: dict = Depends(get_current_user)):
    username = current_user["username"]
    quiz_id = data["quiz_id"]

    session = quiz_col.find_one({"username": username, "quiz_id": quiz_id})
    if not session:
        raise HTTPException(404, "Sessione non trovata")

    user_answers = list(answers_col.find({"username": username, "quiz_id": quiz_id}))
    total_questions = len(BASE_QUIZ)

    # --- CALCOLO PUNTEGGIO ---
    correct_count = sum(1 for a in user_answers if a.get("correct", False))
    score = round((correct_count / total_questions) * 100, 2) if total_questions > 0 else 0
    all_correct = (correct_count == total_questions)

    # --- RECUPERO DATI UTENTE ---
    user = users_col.find_one({"username": username})
    if not user or "seed" not in user or "xrpl_address" not in user:
        raise HTTPException(400, "Dati XRPL mancanti")
    
    user_seed = user["seed"]
    destination_address = user["xrpl_address"]

    # --- 1. INVIO PROOF (USER → SERVER) ---
    proof_tx_hash = "nessuna"
    try:
        proof_data = {
            "username": username,
            "quiz_id": quiz_id,
            "score": score,
            "timestamp": time.time(),
            "answers": [
                {"q": a["question_id"], "choice": a["choice"], "correct": a["correct"]}
                for a in user_answers
            ]
        }
        proof_tx_hash = await send_quiz_proof(user_seed, proof_data)
    except Exception as e:
        print(f"Errore invio proof: {e}")
        proof_tx_hash = "proof_error"

    # --- 2. CALCOLO PREMIO + INVIO (SERVER → USER) ---
    prize_tx_hash = "nessuna"
    prize_message = ""

    if score == 100:
        prize_tx_hash = await send_prize(destination_address, 200)
        prize_message = "JACKPOT! Hai vinto 200 XRP!"
    elif score >= 90:
        prize_tx_hash = await send_prize(destination_address, 10)
        prize_message = "Premio di consolazione: 10 XRP!"
    else:
        prize_message = "Nessuna vincita (serviva almeno 90%)"

    # --- SALVA TUTTO ---
    quiz_col.update_one(
        {"username": username, "quiz_id": quiz_id},
        {"$set": {
            "score": score,
            "all_correct": all_correct,
            "proof_tx_hash": proof_tx_hash,
            "prize_tx_hash": prize_tx_hash,
            "prize_message": prize_message,
            "prize": prize_message,
            "completed": True,
            "completed_at": time.time()
        }},
        upsert=True
    )

    return {
        "msg": "Quiz completato!",
        "score": score,
        "all_correct": all_correct,
        "proof_tx_hash": proof_tx_hash,
        "prize_tx_hash": prize_tx_hash,
        "prize": prize_message
    }

@router.get("/result")
async def get_result(quiz_id: int, current_user: dict = Depends(get_current_user)):
    username = current_user["username"]
    result = quiz_col.find_one(
        {"username": username, "quiz_id": quiz_id, "completed": True},
        {"_id": 0, "score": 1, "prize": 1, "proof_tx_hash": 1, "prize_tx_hash": 1}
    )
    if not result:
        raise HTTPException(404, "Risultato non trovato")
    return result