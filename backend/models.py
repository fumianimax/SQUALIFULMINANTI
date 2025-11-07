from pydantic import BaseModel

class UserRegister(BaseModel):
    username: str
    password: str

class UserLogin(BaseModel):
    username: str
    password: str

class AnswerSubmission(BaseModel):
    question_id: int
    answer: str
    quiz_id: str
