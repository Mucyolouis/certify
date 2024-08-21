<?php

namespace App\Services;

use App\Models\TransferRequest;
use Phpml\Classification\SVC;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Tokenization\WhitespaceTokenizer;
use Illuminate\Support\Facades\Log;

class TransferPredictionService
{
    protected $model;
    protected $tfidf;
    protected $tokenizer;
    protected $vocabulary;
    protected $isTrained = true;

    public function __construct()
    {
        $this->model = new SVC();
        $this->tfidf = new TfIdfTransformer();
        $this->tokenizer = new WhitespaceTokenizer();
        $this->vocabulary = [];
    }

    public function train()
    {
        $samples = [];
        $labels = [];
        
        $transferRequests = TransferRequest::with(['christian', 'fromChurch', 'toChurch'])->get();
        // Ensure $transferRequests is a valid collection, even if it's empty
        if ($transferRequests === null) {
            $transferRequests = collect();
        }
        $transferRequests = $transferRequests ?: collect();
        //dd($transferRequests);
        if ($transferRequests->isEmpty()) {
            throw new \Exception("No transfer requests found. Unable to train the model.");
        }

        Log::info("Number of transfer requests: " . $transferRequests->count());

        // First pass: build vocabulary
        foreach ($transferRequests as $request) {
            $features = $this->prepareFeatures($request);
            Log::debug("Prepared features for request {$request->id}: " . json_encode($features));
            $this->buildVocabulary($features);
        }

        Log::info("Vocabulary size: " . count($this->vocabulary));
        Log::debug("Vocabulary: " . json_encode($this->vocabulary));

        // Second pass: create samples
        foreach ($transferRequests as $request) {
            $tokenizedFeatures = $this->tokenizeFeatures($this->prepareFeatures($request));
            Log::debug("Tokenized features for request {$request->id}: " . json_encode($tokenizedFeatures));
            
            if (!empty(array_filter($tokenizedFeatures))) {
                $samples[] = $tokenizedFeatures;
                $labels[] = $request->approval_status;
            } else {
                Log::warning("Empty features for request {$request->id}");
            }
        }

        Log::info("Number of samples after tokenization: " . count($samples));
        Log::info("Number of labels after tokenization: " . count($labels));

        if (empty($samples) || empty($labels)) {
            throw new \Exception("No valid samples or labels generated after tokenization.");
        }

        // Transform text data to TF-IDF features
        $this->tfidf->fit($samples);
        $transformedSamples = $this->tfidf->transform($samples);

        if ($transformedSamples !== null) {
            Log::info("Number of samples after TF-IDF transformation: " . count($transformedSamples));
            $samples = $transformedSamples;
        } else {
            Log::error("$transformedSamples is null after TF-IDF transformation");
            throw new \Exception("No valid samples generated after TF-IDF transformation.");
        }

        // Train the model
        $this->model->train($samples, $labels);

        $this->isTrained = true;
        Log::info("Model training completed successfully.");
    }

    public function predict($request)
    {
        if (!$this->isTrained) {
            throw new \Exception("Model is not trained. Please train the model first.");
        }

        $features = $this->prepareFeatures($request);
        $tokenizedFeatures = $this->tokenizeFeatures($features);
        $transformedFeatures = $this->tfidf->transform([$tokenizedFeatures]);

        return $this->model->predict($transformedFeatures)[0];
    }

    protected function prepareFeatures($request)
    {
        if ($request instanceof TransferRequest) {
            return [
                'role' => $request->christian->role ?? '',
                'from_church' => $request->fromChurch->name ?? '',
                'to_church' => $request->toChurch->name ?? '',
                'description' => $request->description ?? '',
                'age' => $request->christian->age ?? '',
                'gender' => $request->christian->gender ?? '',
                // Add more relevant features here
            ];
        } elseif (is_array($request)) {
            return [
                'role' => $request['christian']['role'] ?? '',
                'from_church' => $request['from_church']['name'] ?? '',
                'to_church' => $request['to_church']['name'] ?? '',
                'description' => $request['description'] ?? '',
                'age' => $request['christian']['age'] ?? '',
                'gender' => $request['christian']['gender'] ?? '',
                // Add more relevant features here
            ];
        } else {
            throw new \InvalidArgumentException("Invalid input type for prediction. Expected TransferRequest object or array.");
        }
    }

    protected function buildVocabulary(array $features)
    {
        foreach ($features as $key => $value) {
            $tokens = $this->tokenizer->tokenize($this->sanitizeString($value));
            foreach ($tokens as $token) {
                $vocabularyKey = $key . '_' . $token;
                if (!isset($this->vocabulary[$vocabularyKey])) {
                    $this->vocabulary[$vocabularyKey] = count($this->vocabulary);
                }
            }
        }
    }

    protected function tokenizeFeatures(array $features)
    {
        $tokenizedFeatures = array_fill(0, count($this->vocabulary), 0);
        foreach ($features as $key => $value) {
            $tokens = $this->tokenizer->tokenize($this->sanitizeString($value));
            foreach ($tokens as $token) {
                $vocabularyKey = $key . '_' . $token;
                if (isset($this->vocabulary[$vocabularyKey])) {
                    $tokenizedFeatures[$this->vocabulary[$vocabularyKey]]++;
                }
            }
        }
        return $tokenizedFeatures;
    }

    protected function sanitizeString($input)
    {
        if (!is_string($input)) {
            return '';
        }
        // Convert to lowercase and remove any character that's not alphanumeric, whitespace, or common punctuation
        return preg_replace('/[^a-z0-9\s\.\,\-]/', '', strtolower($input));
    }
}