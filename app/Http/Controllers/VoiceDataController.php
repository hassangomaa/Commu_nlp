<?php

// app/Http/Controllers/VoiceDataController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VoiceData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use speech_recognition as sr;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="API Documentation",
 *     version="1.0.0",
 *     description="API Documentation for your project",
 *     @OA\Contact(
 *         email="your-email@example.com",
 *         name="Your Name"
 *     )
 * )
 */
class VoiceDataController extends Controller
{



    /**
     * Store the voice data and perform recognition.
     *
     * @OA\Post(
     *     path="/api/voice/recognize",
     *     tags={"Voice Data"},
     *     summary="Recognize and store voice data",
     *     description="Stores the audio file, performs voice recognition, and returns the recognized text.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="audio",
     *                     description="The audio file to recognize (allowed types: audio/mpeg, audio/wav).",
     *                     type="file",
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="original_text",
     *                 description="The original recognized text.",
     *                 type="string",
     *             ),
     *             @OA\Property(
     *                 property="filtered_text",
     *                 description="The filtered recognized text.",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 description="The validation errors.",
     *                 type="object",
     *             ),
     *         ),
     *     ),
     * )
     */

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
