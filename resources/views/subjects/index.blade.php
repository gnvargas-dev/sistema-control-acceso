<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <style>
        :root {
            --azul-oscuro: #0f172a;
            --azul-medio: #1e3a8a;
            --azul-brillante: #2563eb;
            --azul-claro: #3b82f6;
            --azul-fiel: #60a5fa;
            --blanco-transparente: rgba(255, 255, 255, 0.85);
            --texto-principal: #1e293b;
            --texto-secundario: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Fondo con un degradado dinámico de azules */
            background: linear-gradient(135deg, var(--azul-oscuro), var(--azul-medio), var(--azul-claro));
            padding: 20px;
        }

        .login-card {
            background: var(--blanco-transparente);
            backdrop-filter: blur(10px); /* Efecto cristal esmerilado */
            -webkit-backdrop-filter: blur(10px);
            width: 100%;
            max-width: 420px;
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
        }

        h1 {
            color: var(--azul-medio);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            color: var(--texto-secundario);
            font-size: 0.95rem;
            margin-bottom: 32px;
        }

        .welcome-msg {
            color: var(--azul-oscuro);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
            position: relative;
        }

        label {
            display: block;
            color: var(--texto-principal);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            font-size: 1rem;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            color: var(--texto-principal);
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9);
        }

        /* Efecto de enfoque azul al escribir */
        input:focus {
            border-color: var(--azul-brillante);
            box-shadow: 0 0 0 4px rgba(37, 99, 219, 0.15);
        }

        /* Contenedor específico para manejar el botón "Mostrar" contraseña */
        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--azul-brillante);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: var(--azul-medio);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            background-color: var(--azul-brillante);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
            margin-top: 12px;
            box-shadow: 0 4px 12px rgba(37, 99, 219, 0.3);
        }

        .btn-submit:hover {
            background-color: var(--azul-medio);
        }

        .btn-submit:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <h1>Iniciar sesión</h1>
        <p class="subtitle">Accede al sistema con tus credenciales</p>
        
        <p class="welcome-msg">¡Bienvenido! Ingresa tus datos para continuar.</p>

        <form action="/login" method="POST">
            <div class="form-group">
                <label for="cedula">Número de cédula</label>
                <input type="text" id="cedula" name="username" placeholder="Ingresa tu número de cédula" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">Mostrar</button>
                </div>
            </div>

            <button type="submit" class="btn-submit">Iniciar sesión</button>
        </form>
    </div>

    <script>
        // Función interactiva para ocultar/mostrar la contraseña
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = 'Ocultar';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = 'Mostrar';
            }
        }
    </script>

</body>
</html>