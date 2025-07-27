# Biblioteka

Aplikacja obsługuje podstawowy mechanizm biblioteki za pośrednictwem API.

## Instalacja aplikacji

Instalacja projektu:
- uruchom komendę `docker-compose up -d`, pobiera i konfiguruje kontenery potrzebne do działania
- następnie `docker-compose exec php bash`, za jej pomocą dostajesz się do kontenera
- w kontenerze `composer install`, instaluje brakujący kod
- następnie `symfony console lexik:jwt:generate-keypair`, utworzy klucze wymagane do poprawnego działania autoryzacji JWT
- uruchom komendę `symfony console doctrine:migrations:migrate -n`, uruchomi migrację, która utworzy tabele w bazie danych
- (opcjonalnie) `symfony console doctrine:fixtures:load --append`, wypełni tabele przykładowymi danymi

I to już wszystko! Masz działający projekt biblioteki. Sprawdź czy działa i odwiedź stronę [localhost](http://localhost:8080/).</br>
Polecam się teraz zapoznać z wypisanymi w spisie treści dostępnymi funkcjonalnościami.</br>
\* pamiętaj o tym, że niektóre przeglądarki jak np. Google Chrome blokują domeny http, w takim wypadku musisz zezwolić na działanie localhost w ustawieniach

## Api
Każde zapytanie, z wyjątkiem logowania, jest zabezpieczone przed dostępem dla niezalogowanych użytkowników.
Pamiętaj, aby przed jego wywołaniem ustawić otrzymany token w nagłówku autoryzacji (barierę). Wszystkie opisane zapytania zawierają już przykładowe dane.

### Logowanie
\* do obsługi logowania została użyta autoryzacja JWT (lexik/jwt-authentication-bundle)</br>
POST `{{domain}}/api/auth/login`
```json
{
    "email": "jamessmith@email.com",
    "password": "Password123"
}
```
</br>odpowiedź
```json
{
    "token": "eyJ0eXAiO...."
}
```

### Pobranie książek z bazy
\* do zapytania możliwe jest dodanie filtracji np. `?title=The Lord of the Rings:`</br>
GET `{{domain}}/api/books`
</br>odpowiedź
```json
[
    {
        "title": "The Lord of the Rings: The Fellowship of the Ring",
        "author": "John Ronald Reuel Tolkien",
        "isbn": "978-0547928210",
        "publicationYear": 1954,
        "copiesNumber": 2
    },
    {
        "title": "The Lord of the Rings: The Two Towers",
        "author": "John Ronald Reuel Tolkien",
        "isbn": "978-0547928203",
        "publicationYear": 1962,
        "copiesNumber": 2
    },
    ....
]
```

### Dodanie książki
\* tą czynność może wykonać użytkownik z rolą `LIBRARIAN`</br>
POST `{{domain}}/api/books`
```json
{
    "title": "The Hobbit, or There and Back Again",
    "author": "John Ronald Reuel Tolkien",
    "isbn": "9780261103344",
    "publicationYear": 1937,
    "copiesNumber": 3
}
```
</br>odpowiedź
```json
{
    "title": "The Hobbit, or There and Back Again",
    "author": "John Ronald Reuel Tolkien",
    "isbn": "9780261103344",
    "publicationYear": 1937,
    "copiesNumber": 3
}
```

### Pobranie pojedynczej książki
GET `{{domain}}/api/books/{id}`
</br>odpowiedź
```json
{
    "title": "The Witcher: Blood of Elves",
    "author": "Andrzej Sapkowski",
    "isbn": "978-0-575-08484-1",
    "publicationYear": 1994,
    "copiesNumber": 1
}
```

### Wypożyczenie książki
\* wypożyczenie zostanie przypisane do aktualnego zalogowanego konta</br>
POST `{{domain}}/api/loans`
```json
{
    "bookId": 1
}
```
</br>odpowiedź
```json
{
    "book": {
        "title": "The Witcher: The Last Wish",
        "author": "Andrzej Sapkowski"
    },
    "user": {
        "name": "John",
        "surname": "Doe",
        "email": "johndoe@email.com"
    },
    "borrowDate": "2025-07-27T23:26:08+00:00",
    "returnDate": null
}
```

## Zwrot książki
PUT `{{domain}}/api/loans/{id}/return`
\* jako id powinno zostać podane id wypożyczenia</br>
</br>odpowiedź
```json
{
    "book": {
        "title": "The Lord of the Rings: The Fellowship of the Ring",
        "author": "John Ronald Reuel Tolkien"
    },
    "user": {
        "name": "James",
        "surname": "Smith",
        "email": "jamessmith@email.com"
    },
    "borrowDate": "2025-06-08T00:00:00+00:00",
    "returnDate": "2025-07-27T23:28:12+00:00"
}
```

## Historia wypożyczeń użytkownika
GET `{{domain}}/api/users/{id}/loans`
\* zalogowany użytkownik może pobrać tylko dane o wypożyczonych przez siebie książkach,
wyjątkiem są użytkownicy z rolą `LIBRARIAN` którzy mogą pobrać dane wszystkich użytkowników</br>
</br>odpowiedź
```json
[
    {
        "book": {
            "title": "The Witcher: The Last Wish",
            "author": "Andrzej Sapkowski"
        },
        "user": {
            "name": "John",
            "surname": "Doe",
            "email": "johndoe@email.com"
        },
        "borrowDate": "2024-11-01T00:00:00+00:00",
        "returnDate": "2024-12-11T00:00:00+00:00"
    },
    {
        "book": {
            "title": "The Lord of the Rings: The Fellowship of the Ring",
            "author": "John Ronald Reuel Tolkien"
        },
        "user": {
            "name": "John",
            "surname": "Doe",
            "email": "johndoe@email.com"
        },
        "borrowDate": "2024-07-19T00:00:00+00:00",
        "returnDate": "2024-09-22T00:00:00+00:00"
    },
    ...
]
```
