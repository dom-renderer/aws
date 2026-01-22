<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice - {{ $order->order_number }}</title>

<style>
*{margin:0;padding:0;box-sizing:border-box}
body{
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size:11px;
    background:#f5f8fd;
    color:#0f172a;
}
.invoice-wrapper{
    max-width:820px;
    margin:30px auto;
    background:#fff;
    border-radius:10px;
    padding:30px;
}

/* Header */
.invoice-header{
    border-bottom:1px solid #e6ebf2;
    padding-bottom:20px;
    margin-bottom:25px;
}
.invoice-header h1{
    font-size:22px;
    color:#1f3b6f;
    margin-bottom:6px;
}
.meta{
    color:#6b7a90;
    font-size:11px;
}
.status{
    display:inline-block;
    padding:4px;
    font-size:10px;
    font-weight:700;
    border-radius:20px;
    margin-top:7px;
    text-transform:uppercase;
}
.status-pending{background:#fff7ed;color:#b45309}
.status-confirmed{background:#ecfeff;color:#0369a1}
.status-processing{background:#eff6ff;color:#1d4ed8}
.status-shipped,
.status-delivered{background:#ecfdf5;color:#047857}
.status-cancelled{background:#fef2f2;color:#b91c1c}

/* Cards */
.card{
    border:1px solid #e6ebf2;
    border-radius:8px;
    padding:15px;
    margin-bottom:20px;
}
.card-title{
    font-size:11px;
    font-weight:700;
    color:#1f3b6f;
    margin-bottom:10px;
    text-transform:uppercase;
    letter-spacing:.5px;
}

/* Address */
.address-grid{
    display:table;
    width:100%;
}
.address{
    display:table-cell;
    width:50%;
    padding-right:15px;
}
.address:last-child{padding-right:0;padding-left:15px}

/* Items */
table{
    width:100%;
    border-collapse:collapse;
}
thead{
    background:#f5f8fd;
}
th{
    padding:10px;
    font-size:11px;
    text-align:left;
    color:#1f3b6f;
}
td{
    padding:12px 10px;
    border-bottom:1px solid #e6ebf2;
}
.text-right{text-align:right}
.text-center{text-align:center}
.item-name{font-weight:700}
.item-meta{font-size:10px;color:#6b7a90}

/* Summary */
.summary{
    width:320px;
    margin-left:auto;
}
.summary table td{
    padding:6px 0;
}
.summary .total{
    font-size:14px;
    font-weight:700;
    border-top:2px solid #1f3b6f;
    padding-top:10px;
}

/* Footer */
.footer{
    margin-top:30px;
    text-align:center;
    font-size:10px;
    color:#6b7a90;
}
</style>
</head>

<body>
<div class="invoice-wrapper">

    <!-- HEADER -->
    <div class="invoice-header">
        <h1>Invoice</h1>
        <div class="meta">
            Order #{{ $order->order_number }} |
            <span class="status status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
        </div>
    </div>

    <!-- ADDRESSES -->
    <div class="card">
        <div class="address-grid">
            <div class="address">
                <div class="card-title">Ship To</div>
                <strong>{{ $order->customer->name }}</strong><br>
                {{ $order->location->address_line_1 ?? '' }}<br>
                {{ $order->location->city->name ?? '' }},
                {{ $order->location->state->name ?? '' }}
            </div>
            <div class="address">
                <div class="card-title">Bill To</div>
                <strong>{{ $order->customer->name }}</strong><br>
                {{ $order->billing_address_line_1 ?? '' }}<br>
                {{ $order->billing_city_id ? \App\Models\City::find($order->billing_city_id)->name : '' }}
            </div>
        </div>
    </div>

    <!-- ITEMS -->
    <div class="card">
        <div class="card-title">Order Items</div>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        <div class="item-name">{{ $item->product_name }}</div>
                        <div class="item-meta">
                            SKU: {{ $item->product_sku }} | {{ $item->variant_name }}
                        </div>
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->price_per_unit,2) }}</td>
                    <td class="text-right"><strong>${{ number_format($item->total,2) }}</strong></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- SUMMARY -->
    <div class="card summary">
        <table>
            <tr><td>Subtotal</td><td class="text-right">${{ number_format($order->subtotal,2) }}</td></tr>
            <tr><td>Shipping</td><td class="text-right">${{ number_format($order->shipping_amount,2) }}</td></tr>
            <tr><td>Tax</td><td class="text-right">${{ number_format($order->tax_amount,2) }}</td></tr>
            <tr class="total"><td>Total</td><td class="text-right">${{ number_format($order->total_amount,2) }}</td></tr>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <strong>Anjo Wholesale</strong><br>
        ​P.O. Box 104<br>
        ​St. John's, Antigua & Barbuda <br>
        www.anjowholesale.com
    </div>

</div>
</body>
</html>
