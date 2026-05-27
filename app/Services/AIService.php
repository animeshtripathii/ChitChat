<?php

namespace App\Services;

use App\Models\AILog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AIService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        // Treat dummy placeholder as no key to trigger local Sandbox mode
        if ($this->apiKey === 'your_gemini_api_key_here' || empty(trim($this->apiKey))) {
            $this->apiKey = null;
        }
    }

    /**
     * Moderate a message body.
     * Returns true if the message is unsafe/inappropriate.
     */
    public function isUnsafe(string $text, ?User $user = null): bool
    {
        if (empty(trim($text))) {
            return false;
        }

        $startTime = microtime(true);
        $status = 'success';
        $responseContent = '';
        $tokens = str_word_count($text) + 20; // safe approximation for prompt tokens
        $model = 'gemini-2.5-flash';

        $prompt = "You are an automated content moderation bot. Analyze if the following message contains violence, severe vulgar language, threats, abuse, cyberbullying, or explicit hate speech. If the message is unsafe or inappropriate, reply with the single word 'BAD'. If the message is completely safe and appropriate for a children's playroom chat, reply with the single word 'SAFE'. Do not include any other words or punctuation.\n\nMessage: \"{$text}\"";

        if (!$this->apiKey) {
            // Offline/Sandbox Mock Mode: Keyword Filtering
            $badWords = ['kill', 'murder', 'abuse', 'violence', 'hate', 'threat', 'stupid', 'bastard', 'asshole', 'fuck', 'shit'];
            $normalized = strtolower($text);
            $isUnsafe = false;
            foreach ($badWords as $word) {
                if (str_contains($normalized, $word)) {
                    Log::info("[AIService Sandbox Mode] Flagged unsafe message: \"{$text}\" due to word \"{$word}\"");
                    $isUnsafe = true;
                    break;
                }
            }
            $responseContent = $isUnsafe ? 'BAD' : 'SAFE';
            
            $latency = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($user, $model, $prompt, $responseContent, 'success', $latency, $tokens);
            return $isUnsafe;
        }

        try {
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $generated = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
                Log::info("[AIService Gemini] Moderation result for \"{$text}\": {$generated}");
                $responseContent = $generated;
                $isUnsafe = strtolower($generated) === 'bad';

                $latency = (int)((microtime(true) - $startTime) * 1000);
                $this->logRequest($user, $model, $prompt, $responseContent, 'success', $latency, $tokens);
                return $isUnsafe;
            }

            $status = 'failed';
            $responseContent = "API request failed: " . $response->body();
            Log::error("[AIService Gemini] API request failed: " . $response->body());
        } catch (Exception $e) {
            $status = 'failed';
            $responseContent = "Moderation Exception: " . $e->getMessage();
            Log::error("[AIService Gemini] Moderation Exception: " . $e->getMessage());
        }

        $latency = (int)((microtime(true) - $startTime) * 1000);
        $this->logRequest($user, $model, $prompt, $responseContent, $status, $latency, $tokens);
        return false;
    }

    /**
     * Generate a contextual smart reply for a message.
     */
    public function generateSmartReply(string $incomingText, ?User $user = null): string
    {
        if (empty(trim($incomingText))) {
            return "Hello there! 👋";
        }

        $startTime = microtime(true);
        $status = 'success';
        $responseContent = '';
        $tokens = str_word_count($incomingText) + 30; // safe approximation for prompt tokens
        $model = 'gemini-2.5-flash';

        // Extract personalized instructions and tone from user Settings
        $tone = 'Professional';
        $instructions = '';
        if ($user && $user->aiSettings) {
            $tone = $user->aiSettings->tone ?: 'Professional';
            $instructions = $user->aiSettings->prompt_behavior ?: '';
        }

        $prompt = "You are an automated AI assistant auto-replying on behalf of a user in a modern chat application named ChitChat. "
            . "Generate a short, natural, and context-appropriate reply (1 sentence max) to the following incoming message.\n"
            . "Your tone should be: {$tone}.\n";
        
        if (!empty($instructions)) {
            $prompt .= "Follow these custom personality guidelines: {$instructions}\n";
        }
        
        $prompt .= "\nIncoming Message: \"{$incomingText}\"\n\nReply:";

        // Prepare Sandbox / Fallback Replies list
        $replies = [
            "That sounds super cool! Let's get on it! 🚀",
            "Wow, that's awesome! Let me know if you need help. 🎨",
            "Great point. I will review and update you soon. 👍",
            "Haha, that's so funny! Let's chat more later. 😄",
            "Sounds perfect. Let's schedule a sync. 📅",
            "Absolutely! Let's make it happen. 🌟",
            "Interesting. Let's discuss this during our sync! 🤝",
            "No problem at all! Happy to help. 😊"
        ];
        
        // Refine selection based on Tone Preset if in sandbox/fallback mode
        $selectedFallback = $replies[array_rand($replies)];
        if ($tone === 'Direct') {
            $selectedFallback = "Understood. Will look into it.";
        } elseif ($tone === 'Casual') {
            $selectedFallback = "No worries, sounds good! 😎";
        }

        if (!$this->apiKey) {
            // Offline/Sandbox Mock Mode
            $latency = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($user, $model, $prompt, $selectedFallback, 'success', $latency, $tokens);
            return $selectedFallback;
        }

        try {
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
                // Clean quotes if returned
                $reply = trim($reply, '"\'');
                
                $latency = (int)((microtime(true) - $startTime) * 1000);
                $this->logRequest($user, $model, $prompt, $reply, 'success', $latency, $tokens + str_word_count($reply));
                return $reply;
            }

            $status = 'failed';
            $responseContent = "Smart reply request failed: " . $response->body();
            Log::error("[AIService Gemini] Smart reply request failed: " . $response->body());
        } catch (Exception $e) {
            $status = 'failed';
            $responseContent = "Smart Reply Exception: " . $e->getMessage();
            Log::error("[AIService Gemini] Smart Reply Exception: " . $e->getMessage());
        }

        // Return a dynamic tone-tailored mock reply if the API request failed
        $latency = (int)((microtime(true) - $startTime) * 1000);
        $this->logRequest($user, $model, $prompt, $responseContent, $status, $latency, $tokens);
        return $selectedFallback;
    }

    /**
     * Generate a concise gossipy summary of a chat stream for Chugli Bot.
     */
    public function generateSummary(array $messagesList, ?User $user = null): string
    {
        $startTime = microtime(true);
        $status = 'success';
        $model = 'gemini-2.5-flash';

        // Format message logs
        $historyText = '';
        foreach (array_slice($messagesList, -20) as $msg) {
            $sender = $msg['sender_name'] ?? ($msg['sender']['name'] ?? 'Teammate');
            $body = $msg['body'] ?? '';
            $historyText .= "{$sender}: {$body}\n";
        }

        if (empty(trim($historyText))) {
            return "Arre! There are no messages in this chat yet to gossip about! Send some messages first! 🤫";
        }

        $prompt = "You are 'Chugli', a friendly, gossipy AI assistant inside a chat application named ChitChat. "
            . "Provide a very brief, concise, and slightly gossipy summary (1-2 sentences maximum, in a friendly, conversational tone, using a relevant emoji like 🤫 or 🤭) "
            . "of the following recent chat history. Be a bit playful and fun!\n\n"
            . "Chat History:\n{$historyText}\n\nSummary:";

        $tokens = str_word_count($prompt);

        if (!$this->apiKey) {
            // Local fallback sandbox summaries
            $fallbacks = [
                "🤫 Shhh... it looks like you guys are talking about RCB's qualifications and cricket strategies! Focus, focus! 🏏",
                "🤭 Oh ho! The gossip is hot—someone is trying to figure out why CSK got disqualified this season. Standard stuff! 🦁",
                "🤫 Just caught up on the chat history. It's mostly friendly banter and quick sync updates! Keep it going! ✨",
                "🤭 Well, well, well... looks like a lot of cricket discussion going on here. No major secrets yet! 😉"
            ];
            $selected = $fallbacks[array_rand($fallbacks)];
            $latency = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($user, $model, $prompt, $selected, 'success', $latency, $tokens);
            return $selected;
        }

        try {
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
                $reply = trim($reply, '"\'');
                
                $latency = (int)((microtime(true) - $startTime) * 1000);
                $this->logRequest($user, $model, $prompt, $reply, 'success', $latency, $tokens + str_word_count($reply));
                return $reply;
            }

            $status = 'failed';
            Log::error("[AIService Gemini] Chugli summary request failed: " . $response->body());
        } catch (Exception $e) {
            $status = 'failed';
            Log::error("[AIService Gemini] Chugli Summary Exception: " . $e->getMessage());
        }

        $latency = (int)((microtime(true) - $startTime) * 1000);
        $fallback = "🤫 It looks like you're talking about cricket and team qualification details! Let's keep it clean! 🏏";
        $this->logRequest($user, $model, $prompt, $fallback, $status, $latency, $tokens);
        return $fallback;
    }

    /**
     * Log the request metadata to the ai_logs table.
     */
    protected function logRequest(?User $user, string $model, string $prompt, string $response, string $status, int $latency, int $tokens)
    {
        try {
            // Price: $0.000075 / 1k input tokens, $0.0003 / 1k output tokens for Gemini 1.5 Flash.
            // Let's approximate $0.00015 per 1,000 tokens total.
            $cost = ($tokens / 1000) * 0.00015;

            AILog::create([
                'user_id' => $user ? $user->id : null,
                'model' => $model,
                'status' => $status,
                'latency_ms' => $latency,
                'tokens_used' => $tokens,
                'cost' => $cost,
                'prompt' => $prompt,
                'response' => $response,
            ]);
        } catch (Exception $e) {
            Log::error("[AIService] Failed to write AI Log: " . $e->getMessage());
        }
    }
}
