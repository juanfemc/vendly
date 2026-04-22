<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Tienda</title>

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            margin: 0;
        }

        header {
            background: #111;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
        }

        .container {
            padding: 20px;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .card h3 {
            margin: 10px 0;
        }

        button {
            background: #25D366;
            border: none;
            padding: 10px;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }

        a.cart {
            color: white;
            text-decoration: none;
            background: #25D366;
            padding: 8px 12px;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <header>
        <h2>🛍️ Mi Tienda</h2>
        <a href="/cart" class="cart">🛒 Ver carrito</a>
    </header>

    <div class="container">

        <div class="products">
            @foreach ($products as $product)
                <div class="card">

                    <h3>{{ $product->name }}</h3>
                    <p><b>${{ $product->price }}</b></p>

                    <form action="/cart/add/{{ $product->id }}" method="POST">
                        @csrf
                        <button>Agregar</button>
                    </form>

                </div>
            @endforeach
            <a href="https://wa.me/{{ $store->whatsapp }}">
        </div>
    </div>

</body>

</html>
