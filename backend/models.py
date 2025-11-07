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
    jwt_token: str
