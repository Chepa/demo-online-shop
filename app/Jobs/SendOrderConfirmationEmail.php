<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {
    }

    /**
     * Execute the job.
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            if (!$this->order->relationLoaded('user')) {
                $this->order->load('user');
            }
            if (!$this->order->relationLoaded('items.product')) {
                $this->order->load('items.product');
            }

            $user = $this->order->user;

            if (!$user || !$user->email) {
                Log::warning("Не удалось отправить email для заказа {$this->order->id}: email пользователя не указан");

                return;
            }

            Mail::to($user->email)->send(new OrderConfirmationMail($this->order));

            Log::info("Email подтверждения заказа отправлен для заказа №{$this->order->id} на адрес {$user->email}");
        } catch (Throwable $e) {
            Log::error("Ошибка отправки email для заказа {$this->order->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error(
            "Не удалось отправить email для заказа {$this->order->id} после {$this->tries} попыток: "
            . $exception->getMessage()
        );
    }
}
