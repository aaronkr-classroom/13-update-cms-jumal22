<?php
// part a : 설정
declare(strict_types = 1);
include '../includes/database-connection.php';
include '../includes/functions.php';
include '../includes/validate.php'; // 검증 파일

// 변수 초기화 (오타 수정: $_category -> $category, navugation -> navigation)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT); // 숫자
$category = [
    'id'          => $id,
    'name'        => '',
    'description' => '',
    'navigation'  => 0,
]; 

$errors = [
    'warning'      => '',
    'name'         => '',
    'description'  => '',
];

// 아이디가 있다면, 카테고리를 편집해야 하므로 현재 카테고리 가져옴
if ($id) {
    $sql = "SELECT id, name, description, navigation
            FROM category WHERE id = :id;";
    $from_db = pdo($pdo, $sql, ['id' => $id])->fetch();
    
    if (!$from_db) {
        redirect('category.php', ['failure' => 'Category not found.']);
    } else {
        $category = $from_db;
    }
}

// part b : 데이터 가져와서 유효성 검사 및 처리 (POST 요청일 때만 실행)
if ($_SERVER['REQUEST_METHOD'] == 'POST') { // 폼 제출
    $category['name'] = $_POST['name'] ?? '';
    $category['description'] = $_POST['description'] ?? '';
    $category['navigation'] = (isset($_POST['navigation']) and ($_POST['navigation'] == 1)) ? 1 : 0;
    
    // 모든 데이터가 유효한지 확인하고 유효하지 않다면 오류 메세지 생성
    $errors['name'] = (is_text($category['name'], 1, 24))
        ? '' : 'Name should be 1-24 characters';
    $errors['description'] = (is_text($category['description'], 1, 254))  
        ? '' : 'Description should be 1-254 characters';

    $invalid = implode('', $errors); 

    // 검증을 통과했다면 DB 저장 절차 진행
    if ($invalid) {
        $errors['warning'] = 'Please fix errors.';
    } else {
        $arguments = $category;
        
        if ($id) { // 이미 있는 데이터 수정 SQL 준비
            $sql = "UPDATE category
                    SET name = :name, description = :description, navigation = :navigation
                    WHERE id = :id;";
        } else { // INSERT (새로운 데이터 추가 / 생성) SQL 준비
            unset($arguments['id']); // 자동 증가 키이므로 제거
            $sql = "INSERT INTO category (name, description, navigation)
                    VALUES (:name, :description, :navigation);";
        }

        // sql을 실행할때 세가지가 발생할 수 있음
        try {
            pdo($pdo, $sql, $arguments); // 성공하면 저장
            redirect('categories.php', ['success' => 'Category saved!']);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // 이름 중복 에러 발생 시
                $errors['warning'] = 'Category name already in use!';
            } else { // 다른 이유로 예외 발생 시
                throw $e;
            }
        }
    }
}
?>
<?php include '../includes/admin-header.php'; ?>
  <main class="container admin" id="content">
    <form action="category.php?id=<?= $id ?>" method="post" class="narrow">
      <h1><?= $id ? 'Edit' : 'Add' ?> Category</h1>
      
      <?php if ($errors['warning']) { ?>
        <div class="alert alert-danger"><?= html_escape($errors['warning']) ?></div>
      <?php } ?>

      <div class="form-group">
        <label for="name">Name: </label>
        <input type="text" name="name" id="name"
               value="<?= html_escape($category['name']) ?>" class="form-control">
        <span class="errors"><?= $errors['name'] ?></span>
      </div>

      <div class="form-group">
        <label for="description">Description: </label>
        <textarea name="description" id="description"
                  class="form-control"><?= html_escape($category['description']) ?></textarea>
        <span class="errors"><?= $errors['description'] ?></span>
      </div>

      <div class="form-check">
        <input type="checkbox" name="navigation" id="navigation"
               value="1" class="form-check-input"
          <?= ((int)$category['navigation'] === 1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="navigation">Navigation</label>
      </div>

      <input type="submit" value="Save" class="btn btn-primary btn-save">
    </form>
  </main>
<?php include '../includes/admin-footer.php'; ?>