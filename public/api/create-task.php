<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed.',
    ]);
    exit;
}

try {
    $input = json_decode(
        file_get_contents('php://input'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($input)) {
        throw new InvalidArgumentException('Invalid request body.');
    }

    $projectId = filter_var(
        $input['project_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $title = trim((string) ($input['title'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $status = $input['status'] ?? 'todo';
    $priority = $input['priority'] ?? 'medium';
    $dueDate = $input['due_date'] ?? null;

    if ($projectId === false || $projectId === null || $projectId < 1) {
        throw new InvalidArgumentException('A valid project is required.');
    }

    if ($title === '' || mb_strlen($title) > 200) {
        throw new InvalidArgumentException(
            'Task title is required and must be at most 200 characters.'
        );
    }

    if (mb_strlen($description) > 5000) {
        throw new InvalidArgumentException(
            'Description must be at most 5000 characters.'
        );
    }

    if (!is_string($status) ||
        !in_array($status, ['todo', 'in_progress', 'completed'], true)) {
        throw new InvalidArgumentException('Invalid task status.');
    }

    if (!is_string($priority) ||
        !in_array($priority, ['low', 'medium', 'high'], true)) {
        throw new InvalidArgumentException('Invalid task priority.');
    }

    if ($dueDate === '') {
        $dueDate = null;
    }

    if ($dueDate !== null) {
        if (!is_string($dueDate)) {
            throw new InvalidArgumentException('Invalid due date.');
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);
        $dateErrors = DateTimeImmutable::getLastErrors();

        if (
            !$date ||
            ($dateErrors !== false &&
                ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) ||
            $date->format('Y-m-d') !== $dueDate
        ) {
            throw new InvalidArgumentException('Due date must use YYYY-MM-DD format.');
        }
    }

    require __DIR__ . '/../../config/database.php';

    $projectCheck = $pdo->prepare(
        'SELECT id FROM projects WHERE id = :id'
    );
    $projectCheck->execute(['id' => $projectId]);

    if (!$projectCheck->fetch()) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'error' => 'Selected project does not exist.',
        ]);
        exit;
    }

    $statement = $pdo->prepare(
        'INSERT INTO tasks
            (project_id, title, description, status, priority, due_date)
         VALUES
            (:project_id, :title, :description, :status, :priority, :due_date)'
    );

    $statement->execute([
        'project_id' => $projectId,
        'title' => $title,
        'description' => $description !== '' ? $description : null,
        'status' => $status,
        'priority' => $priority,
        'due_date' => $dueDate,
    ]);

    http_response_code(201);

    echo json_encode([
        'success' => true,
        'message' => 'Task created successfully.',
        'data' => [
            'id' => (int) $pdo->lastInsertId(),
            'project_id' => $projectId,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'due_date' => $dueDate,
        ],
    ], JSON_THROW_ON_ERROR);

} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
} catch (JsonException $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON request.',
    ]);
} catch (Throwable $exception) {
    error_log('CampusFlow create-task API error: ' . $exception->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to create task.',
    ]);
}
