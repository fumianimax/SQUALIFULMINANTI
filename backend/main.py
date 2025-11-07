import sys, os
sys.path.append(os.path.dirname(os.path.abspath(os.path.dirname(__file__))))
from fastapi import FastAPI
from backend import auth, quiz
from backend.database import init_db  # importa la funzione init_db

app = FastAPI(title="XRPL Quiz App")

 #Inizializza il database all’avvio dell’app
@app.on_event("startup")
def startup_event():
    init_db()
    print("Database inizializzato all’avvio")

# Registra le rotte
app.include_router(auth.router, prefix="/auth", tags=["Auth"])
app.include_router(quiz.router, prefix="/quiz", tags=["Quiz"])
