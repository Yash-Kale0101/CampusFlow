<?php

$file = __DIR__ . '/public/assets/js/app.js';
$js = file_get_contents($file);

$marker = '    if (projectForm) {';

if (strpos($js, 'function openEditProject(project)') !== false) {
    echo "Edit handler already exists. No changes made.\n";
    exit;
}

$position = strpos($js, $marker);

if ($position === false) {
    fwrite(STDERR, "Insertion point not found. No changes made.\n");
    exit(1);
}

$handler = <<<'JS'
    const editModalElement = document.getElementById("editProjectModal");
    const editForm = document.getElementById("editProjectForm");
    const editModal = editModalElement && window.bootstrap
        ? new bootstrap.Modal(editModalElement)
        : null;
    const editError = document.getElementById("editProjectError");
    const updateButton = document.getElementById("updateProjectButton");

    function openEditProject(project) {
        if (!editForm || !editModal) {
            showNotification("The edit form could not be opened. Refresh and try again.");
            return;
        }

        document.getElementById("editProjectId").value = project.id;
        document.getElementById("editProjectName").value = project.name || "";
        document.getElementById("editProjectDescription").value =
            project.description || "";
        document.getElementById("editProjectStatus").value =
            project.status || "planning";

        if (editError) {
            editError.hidden = true;
            editError.textContent = "";
        }

        editModal.show();
    }

    if (editForm) {
        editForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            if (editError) {
                editError.hidden = true;
                editError.textContent = "";
            }

            const formData = new FormData(editForm);
            const payload = {
                id: Number(formData.get("id")),
                name: String(formData.get("name") || "").trim(),
                description: String(formData.get("description") || "").trim(),
                status: String(formData.get("status") || "planning")
            };

            if (!payload.name) {
                if (editError) {
                    editError.textContent = "Please enter a project name.";
                    editError.hidden = false;
                }
                return;
            }

            if (updateButton) {
                updateButton.disabled = true;
                updateButton.textContent = "Saving...";
            }

            try {
                const response = await fetch("/api/update-project.php", {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.error || "Unable to update project.");
                }

                editModal?.hide();
                showNotification("Project updated successfully.");
                await loadProjects();
            } catch (error) {
                if (editError) {
                    editError.textContent =
                        error.message || "Unable to update project.";
                    editError.hidden = false;
                }
            } finally {
                if (updateButton) {
                    updateButton.disabled = false;
                    updateButton.textContent = "Save changes";
                }
            }
        });
    }

JS;

$updated = substr_replace($js, $handler, $position, 0);

if (file_put_contents($file, $updated) === false) {
    fwrite(STDERR, "Could not save JavaScript file.\n");
    exit(1);
}

echo "Edit form handler added successfully.\n";
