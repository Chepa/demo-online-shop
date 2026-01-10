<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение заказа №{{ $order->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4f46e5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .order-info {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .order-number {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-paid {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-shipped {
            background-color: #e0e7ff;
            color: #3730a3;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .items-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total {
            text-align: right;
            font-size: 20px;
            font-weight: bold;
            color: #4f46e5;
            margin-top: 20px;
        }
        .customer-info {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .customer-info h3 {
            margin-top: 0;
            color: #374151;
        }
        .customer-info p {
            margin: 8px 0;
            color: #6b7280;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Спасибо за ваш заказ!</h1>
    </div>

    <div class="content">
        <div class="order-info">
            <div class="order-number">Заказ №{{ $order->id }}</div>
            <span class="status-badge status-{{ $order->status }}">
                @if($order->status === 'pending')
                    Ожидает оплаты
                @elseif($order->status === 'paid')
                    Оплачен
                @elseif($order->status === 'shipped')
                    Отправлен
                @elseif($order->status === 'completed')
                    Завершен
                @elseif($order->status === 'cancelled')
                    Отменен
                @else
                    {{ $order->status }}
                @endif
            </span>
            <p style="color: #6b7280; margin-top: 10px;">
                Дата заказа: {{ $order->created_at->format('d.m.Y H:i') }}
            </p>
        </div>

        <h2 style="color: #374151; margin-top: 30px;">Состав заказа:</h2>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Товар</th>
                    <th>Количество</th>
                    <th>Цена</th>
                    <th>Сумма</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'Товар #' . $item->product_id }}</td>
                        <td>{{ $item->quantity }} шт.</td>
                        <td>{{ number_format($item->price, 2, ',', ' ') }} ₽</td>
                        <td>{{ number_format($item->price * $item->quantity, 2, ',', ' ') }} ₽</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total">
            Итого: {{ number_format($order->total, 2, ',', ' ') }} ₽
        </div>

        <div class="customer-info">
            <h3>Информация о доставке:</h3>
            <p><strong>Получатель:</strong> {{ $order->customer_name }}</p>
            @if($order->customer_phone)
                <p><strong>Телефон:</strong> {{ $order->customer_phone }}</p>
            @endif
            @if($order->address_line)
                <p><strong>Адрес:</strong> {{ $order->address_line }}</p>
            @endif
            @if($order->city)
                <p><strong>Город:</strong> {{ $order->city }}</p>
            @endif
            @if($order->postal_code)
                <p><strong>Почтовый индекс:</strong> {{ $order->postal_code }}</p>
            @endif
        </div>

        <div class="footer">
            <p>Это автоматическое письмо, пожалуйста, не отвечайте на него.</p>
            <p>Если у вас возникли вопросы, свяжитесь с нашей службой поддержки.</p>
        </div>
    </div>
</body>
</html>
