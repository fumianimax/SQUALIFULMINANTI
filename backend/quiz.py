from fastapi import APIRouter, HTTPException, Depends
from backend.database import quiz_col, answers_col
import time

router = APIRouter()

# esempio: quiz statico (in futuro caricalo da Mongo)
QUIZ = [
    {"id": 1, "question": "Qual è il capitale della Francia?", "options": ["Roma","Parigi","Berlino","Madrid"], "answer": "Parigi"},
    {"id": 2, "question": "Chi ha scritto '1984'?", "options": ["Orwell","Dante","Hemingway","Kafka"], "answer": "Orwell"},
]

@router.get("/quiz/start")
async def start_quiz():
    start_time = time.time()
    return {"quiz": QUIZ, "start_time": start_time}

@router.post("/quiz/answer")
async def submit_answer(data: dict):
    question_id = data["question_id"]
    choice = data["choice"]
    username = data["username"]

    question = next((q for q in QUIZ if q["id"] == question_id), None)
    if not question:
        raise HTTPException(404, "Domanda non trovata")

    correct = (choice == question["answer"])
    answers_col.insert_one({
        "username": username,
        "question_id": question_id,
        "choice": choice,
        "correct": correct,
        "timestamp": time.time()
    })

    return {"correct": correct}
