* {
    box-sizing: border-box;
}
body {
    margin: 0;
    font-family: Arial, sans-serif;
    display: flex;
    background-color: #f4f4f4;
}
.sidebar {
    width: 220px;
    background: #003366;
    color: white;
    height: 100vh;
    position: fixed;
    padding-top: 20px;
}
.sidebar h2 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 18px;
}
.sidebar a {
    display: block;
    padding: 12px 20px;
    color: white;
    text-decoration: none;
    transition: background 0.3s;
}
.sidebar a:hover {
    background-color: #0055aa;
}
.main-content {
    margin-left: 220px;
    padding: 20px;
    width: calc(100% - 220px);
}
.topbar {
    background: white;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 1px solid #ccc;
}
.topbar h1 {
    font-size: 20px;
    color: #003366;
    margin: 0;
}
.logout {
    background: #cc0000;
    color: white;
    padding: 8px 12px;
    border-radius: 5px;
    text-decoration: none;
}
.cards {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}
.card {
    background: white;
    padding: 15px;
    border-radius: 10px;
    flex: 1;
    text-align: center;
    box-shadow: 0 0 5px #ccc;
}
.card h3 {
    margin: 0 0 10px 0;
    color: #003366;
}
.card p {
    font-size: 18px;
    font-weight: bold;
    margin: 0;
    color: #222;
}
.search input[type="text"] {
    padding: 8px;
    width: 250px;
    margin-right: 5px;
}
.search button {
    padding: 8px 12px;
    background: #003366;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 5px;
}
.search button:hover {
    background: #0055aa;
}
.btn {
    padding: 5px 10px;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    font-size: 13px;
    margin-right: 5px;
}
.btn.view {
    background: #555;
}
.btn.approve {
    background: green;
}
.btn.reject {
    background: red;
}
.btn.export {
    background: #007bff;
    display: inline-block;
    margin-right: 10px;
}
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 0 5px #ccc;
    margin-top: 10px;
}
th, td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}
th {
    background: #003366;
    color: white;
}
@media (max-width: 768px) {
    .sidebar, .main-content {
        width: 100%;
        position: static;
    }
    .main-content {
        margin-left: 0;
    }
}
