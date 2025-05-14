# 🏬 InnoMall — Application Web (Symfony 6.4)

## 🧭 Présentation Générale

**InnoMall** est une plateforme web intelligente de gestion de centre commercial développée avec **Symfony 6.4**. Elle intègre une multitude de modules : e-commerce, événements, fidélité, parking intelligent, chatbot IA, et plus.

---

## 🧩 Fonctionnalités Clés

### 👥 Gestion des utilisateurs
- Rôles : `Client`, `Commerçant`, `Administrateur`
- Connexion :
  - Sécurisée avec **ReCAPTCHA**
  - Via **Google OAuth**
- Profil modifiable

---

### 🛒 Achats & Paiement
- Navigation par boutique
- Ajout au **panier**
- Paiement via **Stripe**
- Confirmation par **email** + **reçu PDF**
- Historique des achats

---

### ⭐ Système de Fidélité
- Attribution de **points** lors des interactions utiles
- Notation des magasins (1 à 5 étoiles)
- Feedback client CRUD
- Points visibles dans le profil utilisateur

---

### 🗨️ Module de Posts
- Clients publient des **posts**
- Commentaires et réponses possibles
- Système de **points gamifiés**
- UI responsive avec validations et tooltips

---

### 📅 Événements
- Commerçants créent des événements
- Clients peuvent :
  - Participer
  - Voir les événements auxquels ils assistent
  - Scanner ou partager un **QR Code**

---

### 🅿️ Parking Intelligent
- Réservation de place via interface
- Réception d’un **SMS de confirmation** (Twilio)
- Intégration d’une logique **IoT simulée**

---

### 📢 Réclamations
- Clients soumettent des réclamations aux boutiques
- Admins consultent et y répondent

---

### 🤖 Chatbot Gemini (IA)
- Intégré dans l’interface Symfony (section gauche)
- Propulsé par **l’API Gemini**
- Réponses en temps réel selon la requête utilisateur
- Aide contextuelle intelligente (navigation, infos, feedback)

---

### 🧑‍💼 Espace Admin
- Gestion :
  - Utilisateurs
  - Réclamations
  - Statistiques
  - DABs (affichés sur carte interactive)
- Visualisation des achats du jour
- Tableau de bord responsive (Tailwind/Twig)

---

## ⚙️ Stack Technique

- Symfony 6.4
- Twig / TailwindCSS / Bootstrap
- Doctrine ORM
- API Gemini (LLM)
- Stripe (paiement)
- Twilio (SMS)
- Google OAuth
- ZXing (QR Code)
- Dompdf (PDF)
- MySQL 8

---
