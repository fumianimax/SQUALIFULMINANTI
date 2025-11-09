import logging
logging.getLogger("passlib").setLevel(logging.ERROR)
logging.getLogger("passlib.handlers.bcrypt").setLevel(logging.ERROR)

import sys, os
import warnings

os.environ["PYTHONWARNINGS"] = "ignore::Warning"
warnings.filterwarnings("ignore")

sys.path.append(os.path.dirname(os.path.abspath(os.path.dirname(__file__))))
from fastapi import FastAPI
from backend import auth, quiz
from backend.database import init_db
from fastapi.middleware.cors import CORSMiddleware
from backend.xrpl_utils import fund_server

app = FastAPI(title="XRPL Quiz App")

# Enable the CORS for the frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://127.0.0.1:8080"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize the DB at the start
@app.on_event("startup")
async def startup_event():
    init_db()
    fund_server()
    print("Database initialized at the start")

# Registering routes
app.include_router(auth.router, prefix="/auth", tags=["Auth"])
app.include_router(quiz.router, prefix="/quiz", tags=["Quiz"])
