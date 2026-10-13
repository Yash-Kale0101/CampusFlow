<?php

$file = __DIR__ . '/public/index.html';
$html = file_get_contents($file);

$marker = '</body>';

if (strpos($html, 'id="editProjectModal"') !== false) {
    echo "Edit modal already exists. No changes made.\n";
    exit;
}

$position = strrpos($html, $marker);

if ($position === false) {
    fwrite(STDERR, "Could not find </body>. No changes made.\n");
    exit(1);
}

$modal = <<<'HTML'
    <!-- Edit Project Modal -->
    <div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editProjectForm">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="editProjectModalLabel">Edit project</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editProjectId" name="id">

                        <div class="mb-3">
                            <label for="editProjectName" class="form-label">Project name</label>
                            <input type="text" class="form-control" id="editProjectName" name="name" maxlength="150" required>
                        </div>

                        <div class="mb-3">
                            <label for="editProjectDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editProjectDescription" name="description" rows="3" maxlength="5000"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="editProjectStatus" class="form-label">Status</label>
                            <select class="form-select" id="editProjectStatus" name="status" required>
                                <option value="planning">Planning</option>
                                <option value="in_progress">In progress</option>
                                <option value="completed">Completed</option>
                                <option value="on_hold">On hold</option>
                            </select>
                        </div>

                        <p id="editProjectError" class="text-danger small mb-0" role="alert" hidden></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="updateProjectButton">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

HTML;

$html = substr_replace($html, $modal, $position, 0);

if (file_put_contents($file, $html) === false) {
    fwrite(STDERR, "Could not save HTML file.\n");
    exit(1);
}

echo "Edit Project modal added successfully.\n";
