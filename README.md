# 📌 Pikkupelit - Small Games 🎮

🇷🇺 [Русская версия](README.ru.md)  

**Old-school games on old-school technologies!**
I wanted to brush up on my programming skills and in a month, with AI support, I implemented several Telegram bots with mini-games, where the user plays against the computer.

## 🎮 Available Games:
- 😵 **Hangman**
- 🎲 **Few Random Games**
- 🪨📄✂️ **Rock Paper Scissors**
- 🃏 **Blackjack**
- 💣 **Minesweeper**
- ❌⭕️ **Tic-Tac-Toe**
- 🔴🟡 **4 in a Row**
- 🚢 **Battle Ship**
- 🧩 **N-Puzzle Game**

### 🔧 Technologies Used:
- **Language:** PHP  
- **Database:** MySQL  
- **Libraries:** cURL (for Telegram API requests)  

Each bot uses a database to store the game state.  
Database creation scripts are included at the beginning of each game file.

## 🚀 How to launch a bot:
1. Upload files to the hosting (check the `required` section in the code).
2. Create a database.
3. Create a new Telegram bot via **BotFather** and get a token.
4. (Optional) Configure the bot via BotFather:
   - add `about`, `description`, `commands`
5. Connect a webhook for the bot.
6. Specify the data in `connect.php` (or use `connect.sample.php` as a template).
7. Launch the bot and click **Start** in Telegram.

## 🛠 Bot capabilities:
Bots support several commands and buttons:
- **New game** – start a new game. Only one last game is saved in the database for each user.
- **Help** – instructions on how to play.
- **Games** – a list of all mini-games.
- **Settings** – change the language and sometimes additional game parameters.
