<?php

namespace Examples;

class Todo {

    static function hydrator() {
        return [self::class, 'hydrate'];
    }

    static function hydrate($row) {
        $todo = new \stdClass;
        $todo->id = $row['todo_id'];
        $todo->done = (bool)$row['todo_done'];
        $todo->text = $row['todo_text'];
        return $todo;
    }

    static function create(\PDO $db, $todo) {
        $query = $db->prepare('INSERT INTO todos (todo_text, todo_done) VALUES (?, ?)');
        $query->execute([$todo->text, (bool)@$todo->done]);
        return $db->lastInsertId();
    }

    static function update(\PDO $db, $id, $todo) {
        $oldTodo = self::fetchOne($db, $id);
        $merged = (object)array_merge((array)$oldTodo, (array)$todo);
        $query = $db->prepare('UPDATE todos SET todo_text = ?, todo_done = ? WHERE todo_id = ?');
        $query->execute([$merged->text, (bool)@$merged->done, $id]);
    }

    static function delete(\PDO $db, $id) {
        $query = $db->prepare('DELETE FROM todos WHERE todo_id = ?');
        $query->execute([$id]);
    }

    static function exists(\PDO $db, $id) {
        $query = $db->prepare('SELECT 1 FROM todos WHERE todo_id = ?');
        $query->execute([$id]);
        return count($query->fetchAll()) == 1;
    }

    static function fetchOne(\PDO $db, $id) {
        $query = $db->prepare('SELECT * FROM todos WHERE todo_id = ?');
        $query->execute([$id]);
        return self::hydrate($query->fetch(\PDO::FETCH_ASSOC));
    }

    static function fetchAll(\PDO $db) {
        $query = $db->prepare('SELECT * FROM todos');
        $query->execute();
        return array_map(self::hydrator(), $query->fetchAll(\PDO::FETCH_ASSOC));
    }

    static function isValid($todo) {
        $allowedKeys = ['id', 'text', 'done'];
        foreach (array_keys((array)$todo) as $key) {
            if (!in_array($key, $allowedKeys)) {
                return false;
            }
        }
        if (!isset($todo->text) || strlen(trim(strval($todo->text))) < 3) {
            return false;
        }
        if (isset($todo->done) && !is_bool($todo->done)) {
            return false;
        }
        return true;
    }

    static function setupDb(\PDO $db) {
        $db->query(
<<<SQL
CREATE TABLE IF NOT EXISTS todos (
  todo_id INTEGER PRIMARY KEY AUTOINCREMENT,
  todo_text TEXT NOT NULL,
  todo_done INTEGER NOT NULL DEFAULT 0
)
SQL
        );
    }
}
