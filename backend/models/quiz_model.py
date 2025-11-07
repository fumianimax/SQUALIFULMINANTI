def get_quizzes(db):
    return list(db.quizzes.find({}, {"_id": 0}))

def add_result(db, email, score):
    db.results.insert_one({"email": email, "score": score})
