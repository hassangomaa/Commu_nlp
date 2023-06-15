<?php

// app/Http/Controllers/VoiceDataController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VoiceData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use speech_recognition as sr;

class VoiceDataController extends Controller
{
    public function recognizeAndStore(Request $request)
    {
        // Validate the request
//        $validator = Validator::make($request->all(), [
//            'audio' => 'required|mimetypes:audio/mpeg,audio/wav',
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json(['error' => $validator->errors()], 400);
//        }

        // Store the audio file in the temp directory with a random name
        $audio = $request->file('audio');
        $tempFileName = $this->generateRandomFileName($audio->getClientOriginalExtension());
        $audio->move(public_path('python_dir/temp'), $tempFileName);

        // Execute the voice recognition code
        $pythonScript = public_path('python_dir/voice_nlp.py');
        $wavFilePath = public_path('python_dir/temp/'.$tempFileName);
        $command = "python {$pythonScript} {$wavFilePath}";
        $output = shell_exec($command);

        // Retrieve the recognized text from the output
        $recognizedText = trim($output);

        // Extract the original and filtered text
        $originalText = $this->extractOriginalText($recognizedText);
        $filteredText = $this->extractFilteredText($recognizedText);

        // Store the recognized text in the database
        $voiceData = new VoiceData();
        $voiceData->text = $recognizedText;
        $voiceData->save();

        // Return the original and filtered text as key-value pairs
        return response()->json([
            'original_text' => $originalText,
            'filtered_text' => $filteredText,
        ], 200);
    }

    /**
     * Generate a random file name.
     *
     * @param string $extension
     * @return string
     */
    private function generateRandomFileName($extension)
    {
        $randomName = Str::random(16);
        return "{$randomName}.{$extension}";
    }



    /**
     * Extract the original text from the recognized text.
     *
     * @param string $recognizedText
     * @return string|null
     */
    private function extractOriginalText($recognizedText)
    {
        $originalTextStart = "Original Text: ";
        $originalTextEnd = "\n";
        $startIndex = strpos($recognizedText, $originalTextStart);
        $endIndex = strpos($recognizedText, $originalTextEnd, $startIndex);
        if ($startIndex !== false && $endIndex !== false) {
            return trim(substr($recognizedText, $startIndex + strlen($originalTextStart), $endIndex - $startIndex - strlen($originalTextStart)));
        }
        return null;
    }


    /**
     * Extract the filtered text from the recognized text.
     *
     * @param string $recognizedText
     * @return array|null
     */
    private function extractFilteredText($recognizedText)
    {
        $filteredTextStart = "Filtered Text: ";
        $filteredTextEnd = "']";
        $startIndex = strpos($recognizedText, $filteredTextStart);
        $endIndex = strpos($recognizedText, $filteredTextEnd, $startIndex);
        if ($startIndex !== false && $endIndex !== false) {
            $filteredTextString = substr($recognizedText, $startIndex + strlen($filteredTextStart), $endIndex - $startIndex - strlen($filteredTextStart));
            $filteredTextArray = explode("', '", $filteredTextString);
            return array_map('trim', $filteredTextArray);
        }
        return null;
    }




}
