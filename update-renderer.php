<?php

$file = __DIR__ . '/public/assets/js/app.js';
$js = file_get_contents($file);

$startMarker = '            result.data.forEach((project, index) => {';
$endMarker = "                projectGrid.appendChild(card);\n            });";

$start = strpos($js, $startMarker);
$end = $start === false ? false : strpos($js, $endMarker, $start);

if ($start === false || $end === false) {
    fwrite(STDERR, "Renderer not found. Original file unchanged.\n");
    exit(1);
}

$end += strlen($endMarker);

$replacement = <<<'JS'
            result.data.forEach((project, index) => {
                const card = document.createElement("article");
                card.className = "project-card";

                const color = colors[index % colors.length];
                const icon = icons[index % icons.length];
                const status = statusLabels[project.status] || "Planning";

                const top = document.createElement("div");
                top.className = "project-card-top";

                const symbol = document.createElement("div");
                symbol.className = `project-symbol project-${color}`;

                const symbolIcon = document.createElement("i");
                symbolIcon.className = `bi ${icon}`;
                symbol.appendChild(symbolIcon);

                const actions = document.createElement("div");
                actions.className = "project-actions";

                const editButton = document.createElement("button");
                editButton.type = "button";
                editButton.className = "btn btn-sm btn-outline-primary";
                editButton.textContent = "Edit";
                editButton.addEventListener("click", () => openEditProject(project));

                const deleteButton = document.createElement("button");
                deleteButton.type = "button";
                deleteButton.className = "btn btn-sm btn-outline-danger";
                deleteButton.textContent = "Delete";
                deleteButton.addEventListener("click", async () => {
                    if (!window.confirm("Delete this project? This cannot be undone.")) return;

                    deleteButton.disabled = true;

                    try {
                        const response = await fetch("/api/delete-project.php", {
                            method: "DELETE",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json"
                            },
                            body: JSON.stringify({ id: project.id })
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.error || "Unable to delete project.");
                        }

                        showNotification("Project deleted successfully.");
                        await loadProjects();
                    } catch (error) {
                        showNotification(error.message || "Unable to delete project.");
                        deleteButton.disabled = false;
                    }
                });

                actions.append(editButton, deleteButton);
                top.append(symbol, actions);

                const category = document.createElement("span");
                category.className = "project-category";
                category.textContent = status.toUpperCase();

                const title = document.createElement("h3");
                title.textContent = project.name;

                const description = document.createElement("p");
                description.textContent = project.description || "No description added yet.";

                const footer = document.createElement("div");
                footer.className = "project-card-bottom";

                const badge = document.createElement("span");
                badge.className = `status-badge ${statusClasses[project.status] || "status-review"}`;
                badge.textContent = status;
                footer.appendChild(badge);

                card.append(top, category, title, description, footer);
                projectGrid.appendChild(card);
            });
JS;

$updated = substr_replace($js, $replacement, $start, $end - $start);

if (file_put_contents($file, $updated) === false) {
    fwrite(STDERR, "Could not write JavaScript file.\n");
    exit(1);
}

echo "Project renderer updated successfully.\n";
