<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Mews API Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-6">
<!-- CHATBOT -->
<section class="bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Chatbot (Debug)</h2>

    <div class="border rounded p-4 h-64 overflow-y-auto mb-4 bg-gray-50" id="chatLog">
        <div class="text-gray-500 text-sm">Chat iniciado…</div>
    </div>

    <div class="flex gap-2">
        <input
            id="chatInput"
            placeholder="Escreve uma mensagem…"
            class="border p-2 flex-1"
            onkeydown="if(event.key === 'Enter') sendChat()"
        >

        <button
            onclick="sendChat()"
            class="bg-indigo-600 text-white px-4 py-2 rounded"
        >
            Enviar
        </button>
    </div>

    <details class="mt-4">
        <summary class="cursor-pointer text-sm text-gray-600">
            Debug técnico (request / response)
        </summary>
        <pre id="chatDebug" class="mt-2 bg-gray-100 p-3 text-xs overflow-auto"></pre>
    </details>
</section>
<div class="max-w-6xl mx-auto space-y-10">

    <!-- AUTH -->
    <section class="bg-white p-6 rounded shadow">
        <h2 class="text-xl font-bold mb-4">Authentication</h2>

        <div class="flex gap-2">
            <input id="email" placeholder="Email" class="border p-2 flex-1">
            <input id="password" type="password" placeholder="Password" class="border p-2 flex-1">

            <button onclick="login()" class="bg-blue-600 text-white px-4 py-2 rounded">
                Login
            </button>

            <button onclick="logout()" class="bg-gray-500 text-white px-4 py-2 rounded">
                Logout
            </button>
        </div>

        <p id="authStatus" class="mt-2 text-sm text-gray-600"></p>
    </section>

    <section class="bg-blue-50 border border-blue-200 p-4 rounded shadow-sm mb-6">
        <h2 class="text-lg font-bold text-blue-800 mb-2">
            Example Data (Mews Sandbox)
        </h2>

        <p class="text-sm text-blue-700 mb-3">
            Use the following example data to quickly test the API and chatbot
            without guessing IDs or formats.
        </p>

        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <!-- PROPERTY -->
            <div class="bg-white p-3 rounded border">
                <h3 class="font-semibold mb-1">Property</h3>
                <p>
                    <code class="bg-gray-100 px-1 rounded">hotel_123</code>
                </p>
                <button
                    onclick="
                        a_property.value = 'hotel_123';
                        r_property.value = 'hotel_123';
                    "
                    class="mt-2 text-xs bg-blue-600 text-white px-2 py-1 rounded"
                >
                    Use property_id
                </button>
            </div>

            <!-- CHAT EXAMPLES -->
            <div class="bg-white p-3 rounded border">
                <h3 class="font-semibold mb-1">Chatbot Examples</h3>

                <div class="space-y-2">
                    <button
                        onclick="chatInput.value='Do you have rooms from June 1 to June 3 for 2 adults'"
                        class="block w-full text-left bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded"
                    >
                        “Do you have rooms from June 1 to June 3 for 2 adults”
                    </button>

                    <button
                        onclick="chatInput.value='Any rooms available tomorrow for 1 adult?'"
                        class="block w-full text-left bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded"
                    >
                        “Any rooms available tomorrow for 1 adult?”
                    </button>

                    <button
                        onclick="chatInput.value='Check availability next weekend for 3 adults'"
                        class="block w-full text-left bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded"
                    >
                        “Check availability next weekend for 3 adults”
                    </button>
                </div>
            </div>
        </div>

        <p class="mt-3 text-xs text-blue-600">
            ℹ These examples match the configured Dialogflow intents and backend
            availability rules.
        </p>
    </section>

    <!-- AVAILABILITY -->
    <section class="bg-white p-6 rounded shadow">
        <h2 class="text-xl font-bold mb-4">Availability</h2>

        <div class="grid grid-cols-4 gap-2 mb-4">
            <input id="a_property" placeholder="Property ID" class="border p-2">
            <input id="a_in" type="date" class="border p-2">
            <input id="a_out" type="date" class="border p-2">
            <input id="a_adults" type="number" min="1" placeholder="Adults" class="border p-2">
        </div>

        <button onclick="loadAvailability()" class="bg-green-600 text-white px-4 py-2 rounded">
            Check Availability
        </button>

        <pre id="availabilityResult" class="mt-4 bg-gray-100 p-4 rounded text-sm overflow-auto"></pre>
    </section>

    <!-- RESERVATIONS -->
    <section class="bg-white p-6 rounded shadow">
        <h2 class="text-xl font-bold mb-4">Reservations</h2>

        <div class="grid grid-cols-4 gap-2 mb-4">
            <input id="r_property" placeholder="Property ID" class="border p-2">
            <input id="r_in" type="date" class="border p-2">
            <input id="r_out" type="date" class="border p-2">
            <input id="r_status" placeholder="Status (optional)" class="border p-2">
        </div>

        <button onclick="loadReservations()" class="bg-purple-600 text-white px-4 py-2 rounded">
            Fetch Reservations
        </button>

        <pre id="reservationsResult" class="mt-4 bg-gray-100 p-4 rounded text-sm overflow-auto"></pre>
    </section>

    <!-- CUSTOMERS -->
    <section class="bg-white p-6 rounded shadow">
        <h2 class="text-xl font-bold mb-4">Customers</h2>

        <input id="c_query" placeholder="Search query" class="border p-2 w-full mb-4">

        <button onclick="searchCustomers()" class="bg-orange-600 text-white px-4 py-2 rounded">
            Search Customers
        </button>

        <pre id="customersResult" class="mt-4 bg-gray-100 p-4 rounded text-sm overflow-auto"></pre>
    </section>

</div>

<script>
/* -----------------------
   API HELPER
----------------------- */
function apiGet(url, params = {}) {
    const token = sessionStorage.getItem('token');

    return fetch(url + '?' + new URLSearchParams(params), {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    }).then(async r => {
        const data = await r.json();
        if (!r.ok) throw data;
        return data;
    });
}

/* -----------------------
   AUTH
----------------------- */
async function login() {
    authStatus.textContent = 'Logging in...';

    const res = await fetch('/api/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            email: email.value,
            password: password.value
        })
    });

    const data = await res.json();

    if (!res.ok) {
        authStatus.textContent = data.message || 'Login failed';
        return;
    }

    sessionStorage.setItem('token', data.token);
    authStatus.textContent = 'Logged in ✔';
}

function logout() {
    sessionStorage.removeItem('token');
    authStatus.textContent = 'Logged out';
}

/* -----------------------
   AVAILABILITY
----------------------- */
async function loadAvailability() {
    availabilityResult.textContent = 'Loading...';

    try {
        const data = await apiGet('/api/mews/availability', {
            property_id: a_property.value,
            check_in: a_in.value,
            check_out: a_out.value,
            adults: a_adults.value
        });

        availabilityResult.textContent = JSON.stringify(data, null, 2);
    } catch (e) {
        availabilityResult.textContent = JSON.stringify(e, null, 2);
    }
}

/* -----------------------
   RESERVATIONS
----------------------- */
async function loadReservations() {
    reservationsResult.textContent = 'Loading...';

    try {
        const data = await apiGet('/api/mews/reservations', {
            property_id: r_property.value,
            check_in: r_in.value,
            check_out: r_out.value,
            status: r_status.value
        });

        reservationsResult.textContent = JSON.stringify(data, null, 2);
    } catch (e) {
        reservationsResult.textContent = JSON.stringify(e, null, 2);
    }
}

/* -----------------------
   CUSTOMERS
----------------------- */
async function searchCustomers() {
    customersResult.textContent = 'Loading...';

    try {
        const data = await apiGet('/api/mews/customers/search', {
            q: c_query.value
        });

        customersResult.textContent = JSON.stringify(data, null, 2);
    } catch (e) {
        customersResult.textContent = JSON.stringify(e, null, 2);
    }
}

/* -----------------------
   CHATBOT
----------------------- */
function logChat(text, from = 'bot') {
    const div = document.createElement('div');
    div.className = from === 'user'
        ? 'text-right mb-2'
        : 'text-left mb-2 text-indigo-700';

    div.innerHTML = `
        <span class="inline-block px-3 py-1 rounded ${
            from === 'user'
                ? 'bg-indigo-600 text-white'
                : 'bg-white border'
        }">
            ${text}
        </span>
    `;

    chatLog.appendChild(div);
    chatLog.scrollTop = chatLog.scrollHeight;
}

async function sendChat() {
    const text = chatInput.value.trim();
    if (!text) return;

    chatInput.value = '';
    logChat(text, 'user');

    const token = sessionStorage.getItem('token');

    const payload = {
        text,
        session_id: 'debug-session'
    };

    chatDebug.textContent =
        'REQUEST:\n' + JSON.stringify(payload, null, 2);

    try {
        const res = await fetch('/api/chatbot/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {})
            },
            body: JSON.stringify(payload)
        });

        const rawText = await res.text();

        let data;
        try {
            data = JSON.parse(rawText);
        } catch {
            data = rawText;
        }

        chatDebug.textContent +=
            '\n\nRESPONSE (' + res.status + '):\n' +
            (typeof data === 'string'
                ? data.substring(0, 2000)
                : JSON.stringify(data, null, 2));

        if (!res.ok) {
            logChat('Erro: ver debug técnico', 'bot');
            return;
        }

        if (data.reply?.text) {
            logChat(data.reply.text, 'bot');
        } else if (data.text) {
            logChat(data.text, 'bot');
        } else {
            logChat('Resposta sem texto', 'bot');
        }

    } catch (e) {
        chatDebug.textContent += '\n\nEXCEPTION:\n' + e.toString();
        logChat('Erro de rede — ver debug técnico', 'bot');
    }
}
</script>

</body>
</html>