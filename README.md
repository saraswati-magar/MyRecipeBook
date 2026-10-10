# My Recipe Book

**My Recipe Book** is a full-stack web application designed for organizing, searching, and managing personal recipes. Built with PHP and MySQL, the application includes convenient features like dynamic portion scaling calculator.
---

## Features

* **Smart Recipe Search:** Filter and search recipes dynamically by title or ingredients.
* **Dynamic Serving Scaler:** Recalculate ingredient quantities automatically based on custom serving sizes.
* **Category & Favoriting:** Organize recipes into categories and bookmark quick favorites.
* **User Authentication:** Secure registration and login session management.

---

## Tech Stack

* **Backend:** PHP, MySQL (PDO)
* **Frontend:** HTML5, CSS3, JavaScript
* **Database Management:** phpMyAdmin / XAMPP
* **Tools & Libraries:** Tesseract OCR, Visual Studio Code, Git & GitHub

---

## Local Setup & Installation

To run this project locally using XAMPP:

1. **Clone the Repository:**
   ```bash
   git clone [https://github.com/saraswatimagar007/MyRecipeBook.git](https://github.com/saraswatimagar007/MyRecipeBook.git)

   2. **Move Files to Web Server:**  
   Place the project folder inside your XAMPP `htdocs` directory (e.g., `C:\xampp\htdocs\MyRecipeBook`).

3. **Database Configuration:**
   * Open XAMPP Control Panel and start **Apache** and **MySQL**.
   * Navigate to `http://localhost/phpmyadmin/`.
   * Create a new database named `recipe_db`.
   * Import the database tables/SQL file into `recipe_db`.
   * Verify database connection settings in `config/mysql_db.php`.

4. **Run the Application:**  
   Open your browser and visit:  
   `http://localhost/MyRecipeBook/index.php`

---

## Academic Context

Developed as a Major Project for the **Diploma in Information Technology** program at **Mahalaxmi Polytechnic Institute (CTEVT)**.

---

## Acknowledgments

Special thanks to supervisors and faculty members:
* Miss Jeena Pokhrel
* Sir Gagan Poudel
* Sir Ujwal Pudasaini
