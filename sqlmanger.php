<?php
require("config.php");

class SQLManager {

private $table;
private $select = "*";
private $joins = [];
private $wheres = [];
private $bindings = [];
private $groupBy = [];
private $having = [];
private $orderBy = [];
private $limit;
private $offset;

/* =========================
   RESET QUERY STATE
========================= */

private function resetQuery(){
    $this->select = "*";
    $this->joins = [];
    $this->wheres = [];
    $this->bindings = [];
    $this->groupBy = [];
    $this->having = [];
    $this->orderBy = [];
    $this->limit = null;
    $this->offset = null;
}

/* =========================
   TABLE
========================= */

public function table($table){
    $this->resetQuery();
    $this->table = $table;
    return $this;
}

/* =========================
   SELECT
========================= */

public function select($columns="*"){
    if(is_array($columns)){
        $this->select = implode(", ",$columns);
    } else {
        $this->select = $columns;
    }
    return $this;
}

/* =========================
   DISTINCT
========================= */

public function distinct(){
    $this->select = "DISTINCT " . $this->select;
    return $this;
}

/* =========================
   WHERE
========================= */

public function where($column,$operator,$value){
    $param = ":w" . count($this->bindings);
    $this->wheres[] = "$column $operator $param";
    $this->bindings[$param] = $value;
    return $this;
}

public function orWhere($column,$operator,$value){
    $param = ":w" . count($this->bindings);
    $this->wheres[] = "OR $column $operator $param";
    $this->bindings[$param] = $value;
    return $this;
}

public function whereIn($column,$values){
    $placeholders = [];
    foreach($values as $value){
        $param = ":w" . count($this->bindings);
        $this->bindings[$param] = $value;
        $placeholders[] = $param;
    }
    $this->wheres[] = "$column IN (" . implode(",",$placeholders) . ")";
    return $this;
}

public function whereBetween($column,$min,$max){
    $param1 = ":w" . count($this->bindings);
    $this->bindings[$param1] = $min;

    $param2 = ":w" . count($this->bindings);
    $this->bindings[$param2] = $max;

    $this->wheres[] = "$column BETWEEN $param1 AND $param2";
    return $this;
}

public function whereNull($column){
    $this->wheres[] = "$column IS NULL";
    return $this;
}

public function whereNotNull($column){
    $this->wheres[] = "$column IS NOT NULL";
    return $this;
}

/* =========================
   JOINS
========================= */

public function join($table,$first,$operator,$second){
    $this->joins[] = "INNER JOIN $table ON $first $operator $second";
    return $this;
}

public function leftJoin($table,$first,$operator,$second){
    $this->joins[] = "LEFT JOIN $table ON $first $operator $second";
    return $this;
}

public function rightJoin($table,$first,$operator,$second){
    $this->joins[] = "RIGHT JOIN $table ON $first $operator $second";
    return $this;
}

public function crossJoin($table){
    $this->joins[] = "CROSS JOIN $table";
    return $this;
}

/* =========================
   GROUP BY
========================= */

public function groupBy($columns){
    if(is_array($columns)){
        $this->groupBy = array_merge($this->groupBy,$columns);
    } else {
        $this->groupBy[] = $columns;
    }
    return $this;
}

/* =========================
   HAVING
========================= */

public function having($condition){
    $this->having[] = $condition;
    return $this;
}

/* =========================
   ORDER BY
========================= */

public function orderBy($column,$direction="ASC"){
    $this->orderBy[] = "$column $direction";
    return $this;
}

/* =========================
   LIMIT OFFSET
========================= */

public function limit($limit,$offset=null){
    $this->limit = $limit;
    if($offset !== null){
        $this->offset = $offset;
    }
    return $this;
}

/* =========================
   BUILD SELECT QUERY
========================= */

private function buildSelect(){

    $sql = "SELECT {$this->select} FROM {$this->table}";

    if(!empty($this->joins)){
        $sql .= " " . implode(" ",$this->joins);
    }

    if(!empty($this->wheres)){
        $sql .= " WHERE " . implode(" ",$this->wheres);
    }

    if(!empty($this->groupBy)){
        $sql .= " GROUP BY " . implode(", ",$this->groupBy);
    }

    if(!empty($this->having)){
        $sql .= " HAVING " . implode(" AND ",$this->having);
    }

    if(!empty($this->orderBy)){
        $sql .= " ORDER BY " . implode(", ",$this->orderBy);
    }

    if($this->limit !== null){
        if($this->offset !== null){
            $sql .= " LIMIT {$this->offset},{$this->limit}";
        } else {
            $sql .= " LIMIT {$this->limit}";
        }
    }

    return $sql;
}

/* =========================
   GET RESULT
========================= */

public function get(){
    $sql = $this->buildSelect();
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($this->bindings);
    $this->resetQuery();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   FIRST ROW
========================= */

public function first(){
    $this->limit(1);
    $result = $this->get();
    return $result[0] ?? null;
}

/* =========================
   COUNT
========================= */

public function count(){
    $this->select("COUNT(*) as total");
    $result = $this->first();
    return $result['total'] ?? 0;
}


/* =========================
   INSERT SINGLE
========================= */

public function insert(array $data){

    $columns = array_keys($data);
    $placeholders = [];
    $bindings = [];

    foreach($data as $key=>$value){
        $param = ":i_" . $key;
        $placeholders[] = $param;
        $bindings[$param] = $value;
    }

    $sql = "INSERT INTO {$this->table} ("
            . implode(",", $columns)
            . ") VALUES ("
            . implode(",", $placeholders)
            . ")";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($bindings);

    return $this->pdo->lastInsertId();
}

/* =========================
   INSERT BULK
========================= */

public function insertBulk(array $rows){

    if(empty($rows)) return false;

    $columns = array_keys($rows[0]);
    $sql = "INSERT INTO {$this->table} (".implode(",", $columns).") VALUES ";

    $valuesPart = [];
    $bindings = [];
    $rowIndex = 0;

    foreach($rows as $row){
        $placeholders = [];

        foreach($row as $key=>$value){
            $param = ":b_{$key}_{$rowIndex}";
            $placeholders[] = $param;
            $bindings[$param] = $value;
        }

        $valuesPart[] = "(" . implode(",", $placeholders) . ")";
        $rowIndex++;
    }

    $sql .= implode(",", $valuesPart);

    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute($bindings);
}

/* =========================
   UPDATE
========================= */

public function update(array $data){

    $setParts = [];
    $bindings = $this->bindings;

    foreach($data as $key=>$value){
        $param = ":u_" . $key;
        $setParts[] = "$key = $param";
        $bindings[$param] = $value;
    }

    $sql = "UPDATE {$this->table} SET " . implode(",", $setParts);

    if(!empty($this->wheres)){
        $sql .= " WHERE " . implode(" ", $this->wheres);
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($bindings);

    $this->resetQuery();
    return $stmt->rowCount();
}

/* =========================
   DELETE
========================= */

public function delete(){

    $sql = "DELETE FROM {$this->table}";

    if(!empty($this->wheres)){
        $sql .= " WHERE " . implode(" ", $this->wheres);
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($this->bindings);

    $this->resetQuery();
    return $stmt->rowCount();
}

/* =========================
   UPSERT (ON DUPLICATE KEY)
========================= */

public function upsert(array $data){

    $columns = array_keys($data);
    $placeholders = [];
    $updates = [];
    $bindings = [];

    foreach($data as $key=>$value){
        $param = ":up_" . $key;
        $placeholders[] = $param;
        $updates[] = "$key = VALUES($key)";
        $bindings[$param] = $value;
    }

    $sql = "INSERT INTO {$this->table} ("
            . implode(",", $columns)
            . ") VALUES ("
            . implode(",", $placeholders)
            . ") ON DUPLICATE KEY UPDATE "
            . implode(",", $updates);

    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute($bindings);
}

/* =========================
   EXISTS
========================= */

public function exists(){
    return $this->count() > 0;
}

/* =========================
   UNION
========================= */

public function union($secondQuery){

    $firstSql = $this->buildSelect();
    $sql = "($firstSql) UNION ($secondQuery)";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($this->bindings);

    $this->resetQuery();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   UNION ALL
========================= */

public function unionAll($secondQuery){

    $firstSql = $this->buildSelect();
    $sql = "($firstSql) UNION ALL ($secondQuery)";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($this->bindings);

    $this->resetQuery();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   SUBQUERY SUPPORT
========================= */

public function whereSub($column,$operator,$subQuery){

    $sql = $this->buildSelect();
    $this->wheres[] = "$column $operator ($subQuery)";
    return $this;
}

/* =========================
   CASE EXPRESSION
========================= */

public function selectCase($caseSql){
    $this->select .= ", $caseSql";
    return $this;
}

/* =========================
   TRANSACTION ENGINE
========================= */

public function beginTransaction(){
    return $this->pdo->beginTransaction();
}

public function commit(){
    return $this->pdo->commit();
}

public function rollback(){
    return $this->pdo->rollBack();
}

/* =========================
   SAFE MODE (BLOCK FULL DELETE)
========================= */

public $safeMode = true;

public function disableSafeMode(){
    $this->safeMode = false;
    return $this;
}

public function forceDelete(){

    if($this->safeMode && empty($this->wheres)){
        throw new Exception("Safe Mode: DELETE without WHERE blocked.");
    }

    return $this->delete();
}


/* =========================
   RAW EXECUTION (INTERNAL)
========================= */

private function executeRaw($sql){
    return $this->pdo->exec($sql);
}

/* =========================
   DATABASE MANAGEMENT
========================= */

public function createDatabase($name){
    return $this->executeRaw("CREATE DATABASE IF NOT EXISTS `$name`");
}

public function dropDatabase($name){
    return $this->executeRaw("DROP DATABASE IF EXISTS `$name`");
}

public function useDatabase($name){
    return $this->executeRaw("USE `$name`");
}

/* =========================
   TABLE CREATION (SCHEMA BUILDER)
========================= */

public function createTable($table, callable $callback){

    $blueprint = new stdClass();
    $blueprint->columns = [];
    $blueprint->primary = null;

    $callback($blueprint);

    $columnsSql = implode(",", $blueprint->columns);

    if($blueprint->primary){
        $columnsSql .= ", PRIMARY KEY ({$blueprint->primary})";
    }

    $sql = "CREATE TABLE IF NOT EXISTS `$table` ($columnsSql) ENGINE=InnoDB";

    return $this->executeRaw($sql);
}

/* COLUMN HELPERS */

public function column_id(&$bp){
    $bp->columns[] = "id INT AUTO_INCREMENT";
    $bp->primary = "id";
}

public function column_string(&$bp,$name,$length=255,$nullable=false){
    $null = $nullable ? "NULL" : "NOT NULL";
    $bp->columns[] = "$name VARCHAR($length) $null";
}

public function column_text(&$bp,$name,$nullable=true){
    $null = $nullable ? "NULL" : "NOT NULL";
    $bp->columns[] = "$name TEXT $null";
}

public function column_int(&$bp,$name,$nullable=false){
    $null = $nullable ? "NULL" : "NOT NULL";
    $bp->columns[] = "$name INT $null";
}

public function column_boolean(&$bp,$name){
    $bp->columns[] = "$name TINYINT(1) NOT NULL DEFAULT 0";
}

public function column_timestamp(&$bp,$name="created_at"){
    $bp->columns[] = "$name TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
}

/* =========================
   ALTER TABLE
========================= */

public function addColumn($table,$definition){
    return $this->executeRaw("ALTER TABLE `$table` ADD $definition");
}

public function dropColumn($table,$column){
    return $this->executeRaw("ALTER TABLE `$table` DROP COLUMN `$column`");
}

/* =========================
   INDEX MANAGEMENT
========================= */

public function createIndex($table,$indexName,$column){
    return $this->executeRaw("CREATE INDEX `$indexName` ON `$table`($column)");
}

public function dropIndex($table,$indexName){
    return $this->executeRaw("DROP INDEX `$indexName` ON `$table`");
}

/* =========================
   VIEW MANAGEMENT
========================= */

public function createView($name,$selectQuery){
    return $this->executeRaw("CREATE OR REPLACE VIEW `$name` AS $selectQuery");
}

public function dropView($name){
    return $this->executeRaw("DROP VIEW IF EXISTS `$name`");
}

/* =========================
   TRIGGER MANAGEMENT
========================= */

public function createTrigger($name,$timing,$event,$table,$body){
    $sql = "CREATE TRIGGER `$name` $timing $event ON `$table`
            FOR EACH ROW $body";
    return $this->executeRaw($sql);
}

public function dropTrigger($name){
    return $this->executeRaw("DROP TRIGGER IF EXISTS `$name`");
}

/* =========================
   STORED PROCEDURE
========================= */

public function createProcedure($name,$params,$body){
    $sql = "CREATE PROCEDURE `$name` ($params) $body";
    return $this->executeRaw($sql);
}

public function dropProcedure($name){
    return $this->executeRaw("DROP PROCEDURE IF EXISTS `$name`");
}

/* =========================
   FUNCTION
========================= */

public function createFunction($name,$params,$returns,$body){
    $sql = "CREATE FUNCTION `$name` ($params) RETURNS $returns $body";
    return $this->executeRaw($sql);
}

public function dropFunction($name){
    return $this->executeRaw("DROP FUNCTION IF EXISTS `$name`");
}

/* =========================
   USER MANAGEMENT
========================= */

public function createUser($user,$password){
    return $this->executeRaw("CREATE USER '$user'@'localhost' IDENTIFIED BY '$password'");
}

public function dropUser($user){
    return $this->executeRaw("DROP USER IF EXISTS '$user'@'localhost'");
}

public function grantPrivileges($user,$database,$privileges="ALL PRIVILEGES"){
    return $this->executeRaw("GRANT $privileges ON `$database`.* TO '$user'@'localhost'");
}

public function revokePrivileges($user,$database){
    return $this->executeRaw("REVOKE ALL PRIVILEGES ON `$database`.* FROM '$user'@'localhost'");
}

/* =========================
   FULL BACKUP ENGINE
========================= */

public function fullBackup($file="database_backup.sql"){

    $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $sqlDump = "";

    foreach($tables as $table){

        $createTable = $this->pdo->query("SHOW CREATE TABLE `$table`")
                                 ->fetch(PDO::FETCH_ASSOC);

        $sqlDump .= $createTable['Create Table'] . ";\n\n";

        $rows = $this->pdo->query("SELECT * FROM `$table`")
                          ->fetchAll(PDO::FETCH_ASSOC);

        foreach($rows as $row){

            $columns = array_map(function($col){
                return "`$col`";
            }, array_keys($row));

            $values = array_map(function($val){
                return "'" . addslashes($val) . "'";
            }, array_values($row));

            $sqlDump .= "INSERT INTO `$table` ("
                        . implode(",",$columns)
                        . ") VALUES ("
                        . implode(",",$values)
                        . ");\n";
        }

        $sqlDump .= "\n\n";
    }

    file_put_contents($file,$sqlDump);

    return true;
}


/* =========================
   CONFIG FLAGS
========================= */

public $debugMode = false;
public $enableCache = false;
public $logQueries = true;
private $queryLog = [];
private $cacheStore = [];
private $roles = [];
private $currentRole = null;

/* =========================
   DEBUG MODE
========================= */

public function enableDebug(){
    $this->debugMode = true;
    return $this;
}

private function debug($sql,$bindings){
    if($this->debugMode){
        echo "<pre>";
        print_r($sql);
        print_r($bindings);
        echo "</pre>";
    }
}

/* =========================
   QUERY LOGGER
========================= */

private function logQuery($sql,$time){
    if($this->logQueries){
        $this->queryLog[] = [
            "query"=>$sql,
            "execution_time"=>$time
        ];
    }
}

public function getQueryLog(){
    return $this->queryLog;
}

/* =========================
   PERFORMANCE TIMER
========================= */

private function runWithTimer($sql,$bindings=[]){

    $start = microtime(true);

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($bindings);

    $time = microtime(true) - $start;

    $this->logQuery($sql,$time);
    $this->debug($sql,$bindings);

    return $stmt;
}

/* =========================
   SIMPLE CACHE LAYER
========================= */

public function enableCache(){
    $this->enableCache = true;
    return $this;
}

private function cacheKey($sql,$bindings){
    return md5($sql . serialize($bindings));
}

private function cacheGet($key){
    return $this->cacheStore[$key] ?? null;
}

private function cacheSet($key,$data){
    $this->cacheStore[$key] = $data;
}

/* =========================
   OVERRIDE GET (CACHED)
========================= */

public function getCached(){

    $sql = $this->buildSelect();
    $key = $this->cacheKey($sql,$this->bindings);

    if($this->enableCache && $this->cacheGet($key)){
        return $this->cacheGet($key);
    }

    $stmt = $this->runWithTimer($sql,$this->bindings);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if($this->enableCache){
        $this->cacheSet($key,$result);
    }

    $this->resetQuery();
    return $result;
}

/* =========================
   MIGRATION SYSTEM
========================= */

public function migrate($name,$callback){

    $this->createTable("migrations", function($bp){
        $this->column_id($bp);
        $this->column_string($bp,"migration_name");
        $this->column_timestamp($bp);
    });

    $exists = $this->raw(
        "SELECT COUNT(*) as c FROM migrations WHERE migration_name=?",
        [$name]
    );

    if($exists[0]['c'] == 0){
        $callback($this);
        $this->raw(
            "INSERT INTO migrations (migration_name) VALUES (?)",
            [$name]
        );
    }
}

/* =========================
   ROLE BASED ACCESS CONTROL
========================= */

public function defineRole($role,$permissions){
    $this->roles[$role] = $permissions;
}

public function setRole($role){
    $this->currentRole = $role;
}

private function checkPermission($action){

    if(!$this->currentRole) return true;

    if(!in_array($action,$this->roles[$this->currentRole])){
        throw new Exception("Permission Denied for $action");
    }
}

/* =========================
   SAFE EXECUTION WITH RBAC
========================= */

public function secureRaw($sql,$params=[]){

    if(stripos($sql,"drop")!==false){
        $this->checkPermission("drop");
    }

    if(stripos($sql,"delete")!==false){
        $this->checkPermission("delete");
    }

    return $this->raw($sql,$params);
}

/* =========================
   SOFT DELETE SYSTEM
========================= */

public function softDelete(){
    return $this->update([
        "deleted_at"=>date("Y-m-d H:i:s")
    ]);
}

public function withTrashed(){
    $this->whereNull("deleted_at");
    return $this;
}

/* =========================
   MINI ORM SYSTEM
========================= */

public function model($table){

    return new class($this,$table){

        private $db;
        private $table;

        public function __construct($db,$table){
            $this->db = $db;
            $this->table = $table;
        }

        public function all(){
            return $this->db->table($this->table)->get();
        }

        public function find($id){
            return $this->db->table($this->table)
                            ->where("id","=",$id)
                            ->first();
        }

        public function create($data){
            return $this->db->table($this->table)
                            ->insert($data);
        }

        public function delete($id){
            return $this->db->table($this->table)
                            ->where("id","=",$id)
                            ->delete();
        }
    };
}

/* =========================
   AUTO MODEL GENERATOR
========================= */

public function generateModel($table,$fileName){

    $className = ucfirst($table) . "Model";

    $code = "<?php
class $className {

    private \$db;

    public function __construct(\$db){
        \$this->db = \$db;
    }

    public function all(){
        return \$this->db->table('$table')->get();
    }

    public function find(\$id){
        return \$this->db->table('$table')
                         ->where('id','=',\$id)
                         ->first();
    }
}";

    file_put_contents($fileName,$code);

    return true;
}
}
