import nltk
from nltk.tokenize import word_tokenize
from nltk.corpus import stopwords
from nltk.stem import WordNetLemmatizer
nltk.download('punkt')
nltk.download('stopwords')
nltk.download('averaged_perceptron_tagger')
nltk.download('wordnet')
# User input for the text to process
text = input("Enter text: ")

# Tokenization
tokens = word_tokenize(text)

# Removing stopwords
stop_words = set(stopwords.words('english'))
filtered_tokens = [word for word in tokens if word.lower() not in stop_words]

# Part-of-speech tagging
tagged = nltk.pos_tag(tokens)

# Counting tenses
tense = {
    "future": len([word for word in tagged if word[1] == "MD"]),
    "present": len([word for word in tagged if word[1] in ["VBP", "VBZ", "VBG"]]),
    "past": len([word for word in tagged if word[1] in ["VBD", "VBN"]]),
    "present_continuous": len([word for word in tagged if word[1] in ["VBG"]])
}

# Lemmatization
lemmatizer = WordNetLemmatizer()
lemmatized_tokens = [lemmatizer.lemmatize(token) for token in filtered_tokens]

# Perspective change for first-person pronoun
temp = []
for w in lemmatized_tokens:
    if w == 'I':
        temp.append('Me')
    else:
        temp.append(w)
words = temp

# Determining the most probable tense
probable_tense = max(tense, key=tense.get)

# Modifying the words based on the probable tense
if probable_tense == "past" and tense["past"] >= 1:
    temp = ["Before"]
    temp = temp + words
    words = temp
elif probable_tense == "future" and tense["future"] >= 1:
    if "Will" not in words:
        temp = ["Will"]
        temp = temp + words
        words = temp
    else:
        pass
elif probable_tense == "present":
    if tense["present_continuous"] >= 1:
        temp = ["Now"]
        temp = temp + words
        words = temp

# Printing the results
print("Original Text: ", text)
print("Tokenized Text: ", tokens)
print("Filtered Text: ", filtered_tokens)
print("Lemmatized Text: ", lemmatized_tokens)
