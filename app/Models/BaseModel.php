<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Lightweight base model with a small query builder.
 * All queries go through PDO prepared statements.
 */
abstract class BaseModel
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function table(): string
    {
        return static::$table;
    }

    public static function find(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ? LIMIT 1';
        $stmt = Database::run($sql, [$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findOrFail(int $id): array
    {
        $row = static::find($id);
        if (!$row) {
            throw new \RuntimeException('Record not found.');
        }
        return $row;
    }

    public static function where(string $column, mixed $value): Query
    {
        return (new Query(static::$table))->where($column, $value);
    }

    public static function query(): Query
    {
        return new Query(static::$table);
    }

    /** Query the table with a SQL alias, e.g. queryAs('b') -> FROM table b */
    public static function queryAs(string $alias): Query
    {
        return new Query(static::$table . ' ' . $alias);
    }

    public static function all(): array
    {
        return Database::run('SELECT * FROM ' . static::$table)->fetchAll();
    }

    public static function count(string $where = '1=1', array $params = []): int
    {
        $row = Database::run('SELECT COUNT(*) AS c FROM ' . static::$table . ' WHERE ' . $where, $params)->fetch();
        return (int)$row['c'];
    }

    public static function insert(array $data): int
    {
        if (empty($data)) {
            throw new \RuntimeException('Cannot insert empty data.');
        }
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::$table,
            '`' . implode('`,`', $columns) . '`',
            implode(',', array_fill(0, count($data), '?'))
        );
        Database::run($sql, array_values($data));
        return (int)Database::lastId();
    }

    public static function update(int $id, array $data): int
    {
        if (empty($data)) {
            return 0;
        }
        $sets = array_map(fn($c) => "`$c` = ?", array_keys($data));
        $sql = sprintf('UPDATE %s SET %s WHERE %s = ?', static::$table, implode(',', $sets), static::$primaryKey);
        $params = array_values($data);
        $params[] = $id;
        return Database::run($sql, $params)->rowCount();
    }

    public static function delete(int $id): int
    {
        return Database::run('DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ?', [$id])->rowCount();
    }

    public static function softDelete(int $id): int
    {
        return static::update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }
}

/**
 * Simple fluent query builder.
 */
class Query
{
    private string $table;
    private array $wheres = [];
    private array $params = [];
    private array $orderBys = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private string $select = '*';
    private array $joins = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function select(string $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    public function where(string $column, mixed $a, mixed $b = null): self
    {
        // Accepts where(col, value), where(col, value, operator) and
        // where(col, operator, value) — the operator position is detected
        // automatically so both conventions work.
        $operators = ['=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE'];
        if ($b !== null && is_string($a) && in_array(strtoupper($a), $operators, true)) {
            $operator = $a;
            $value = $b;
        } else {
            $value = $a;
            $operator = $b ?? '=';
        }
        if ($value === null) {
            $this->wheres[] = "$column IS NULL";
        } else {
            $this->wheres[] = "$column $operator ?";
            $this->params[] = $value;
        }
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = "$column IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = "$column IS NOT NULL";
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            $this->wheres[] = '0 = 1';
            return $this;
        }
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = "$column IN ($placeholders)";
        foreach ($values as $v) {
            $this->params[] = $v;
        }
        return $this;
    }

    public function whereLike(string $column, string $value): self
    {
        $this->wheres[] = "$column LIKE ?";
        $this->params[] = '%' . $value . '%';
        return $this;
    }

    public function orWhereLike(string $column, string $value): self
    {
        if (empty($this->wheres)) {
            return $this->whereLike($column, $value);
        }
        // Wrap existing wheres and add an OR branch
        $last = array_pop($this->wheres);
        $this->wheres[] = '(' . $last . ' OR ' . "$column LIKE ?)";
        $this->params[] = '%' . $value . '%';
        return $this;
    }

    public function whereBetween(string $column, mixed $from, mixed $to): self
    {
        $this->wheres[] = "$column BETWEEN ? AND ?";
        $this->params[] = $from;
        $this->params[] = $to;
        return $this;
    }

    public function join(string $table, string $condition): self
    {
        $this->joins[] = "INNER JOIN $table ON $condition";
        return $this;
    }

    public function leftJoin(string $table, string $condition): self
    {
        $this->joins[] = "LEFT JOIN $table ON $condition";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBys[] = "$column $direction";
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    private function buildSql(): string
    {
        $sql = 'SELECT ' . $this->select . ' FROM ' . $this->table;
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        if (!empty($this->orderBys)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBys);
        }
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            if ($this->offset > 0) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        return $sql;
    }

    public function get(): array
    {
        return Database::run($this->buildSql(), $this->params)->fetchAll();
    }

    public function first(): ?array
    {
        $rows = Database::run($this->buildSql() . ' LIMIT 1', $this->params)->fetchAll();
        return $rows[0] ?? null;
    }

    public function count(): int
    {
        $sql = 'SELECT COUNT(*) AS c FROM ' . $this->table;
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        $row = Database::run($sql, $this->params)->fetch();
        return (int)$row['c'];
    }

    public function sum(string $column): string
    {
        $sql = 'SELECT COALESCE(SUM(' . $column . '), 0) AS s FROM ' . $this->table;
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        $row = Database::run($sql, $this->params)->fetch();
        return (string)$row['s'];
    }
}
