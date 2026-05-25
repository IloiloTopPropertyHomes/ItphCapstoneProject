const express = require("express");
const http = require("http");
const { Server } = require("socket.io");
const cors = require("cors");
const mysql = require("mysql2");

const app = express();

app.use(cors());

const server = http.createServer(app);

const io = new Server(server, {
    cors: {
        origin: "*"
    }
});

// Redirect to PHP website
app.get("/", (req, res) => {
    res.redirect("http://localhost/recapstone/index.php");
});

// MySQL
const db = mysql.createConnection({
    host: "localhost",
    user: "root",
    password: "",
    database: "secure_app"
});

db.connect((err) => {
    if(err){
        console.log(err);
    } else {
        console.log("MySQL Connected");
    }
});

io.on("connection", (socket) => {

    console.log("User Connected");

    socket.on("send_message", (data) => {
        io.emit("receive_message", data);
    });

    socket.on("disconnect", () => {
        console.log("User Disconnected");
    });

});

server.listen(3000, () => {
    console.log("Server running on port 3000");
});