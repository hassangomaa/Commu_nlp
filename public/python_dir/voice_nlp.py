import sys
import nltk
from nltk.tokenize import word_tokenize
from nltk.corpus import stopwords
from nltk.stem import WordNetLemmatizer
import speech_recognition as sr

# Check if the correct number of command-line arguments is provided
if len(sys.argv) < 2:
    print("Usage: python voice_nlp.py <path_to_mp3_file>")
    sys.exit(1)

# Retrieve the MP3 file path from the command-line argument
mp3_file = sys.argv[1]

# Create a speech recognizer object
r = sr.Recognizer()

# Load the audio file
with sr.AudioFile(mp3_file) as source:
    audio = r.record(source)

try:
    # Perform speech recognition on the audio
    text = r.recognize_google(audio)

    # Rest of your code...
    tokens = word_tokenize(text)
    stop_words = set(stopwords.words('english'))
    filtered_tokens = [word for word in tokens if word.lower() not in stop_words]
    tagged = nltk.pos_tag(tokens)
    # Rest of your code...

    print("Original Text: ", text)
    print("Filtered Text: ", filtered_tokens)

except sr.UnknownValueError:
    print("Speech Recognition could not understand audio")
except sr.RequestError as e:
    print("Could not request results: {0}".format(e))
