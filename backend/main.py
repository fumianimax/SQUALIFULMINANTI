import sys, os
sys.path.append(os.path.dirname(os.path.abspath(os.path.dirname(__file__))))
from fastapi import FastAPI
from backend import auth, quiz
from fastapi.responses import FileResponse

app = FastAPI(title="XRPL Quiz App")

app.include_router(auth.router, prefix="/auth", tags=["Auth"])
app.include_router(quiz.router, prefix="/quiz", tags=["Quiz"])

@app.get("/")
def serve_frontend():
    index_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), "frontend", "index.php")
    return FileResponse(index_path)
