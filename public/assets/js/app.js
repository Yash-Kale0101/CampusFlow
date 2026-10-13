"use strict";

document.addEventListener("DOMContentLoaded", () => {
    const toastElement = document.getElementById("campusToast");
    const toastMessage = document.getElementById("toastMessage");
    const toast = toastElement && window.bootstrap
        ? new bootstrap.Toast(toastElement)
        : null;

    function showNotification(message) {
        if (toastMessage) toastMessage.textContent = message;
        if (toast) toast.show();
        else console.log(message);
    }

    const createProjectButton = document.getElementById("createProjectButton");
    const projectGrid = document.getElementById("projectGrid");
    const projectForm = document.getElementById("createProjectForm");
    const projectModalElement = document.getElementById("createProjectModal");
    const projectError = document.getElementById("createProjectError");
    const saveProjectButton = document.getElementById("saveProjectButton");

    const modal = projectModalElement && window.bootstrap
        ? new bootstrap.Modal(projectModalElement)
        : null;

    const statusLabels = {
        planning: "Planning",
        in_progress: "In progress",
        completed: "Completed",
        on_hold: "On hold"
    };

    const statusClasses = {
        planning: "status-review",
        in_progress: "status-progress",
        completed: "status-complete",
        on_hold: "status-review"
    };

    // Load live dashboard statistics
    async function loadDashboardStats() {
        try {
            const response = await fetch("/api/dashboard-stats.php", {
                headers: { Accept: "application/json" }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error("Could not load dashboard statistics.");
            }

            const stats = result.data;

            document.getElementById("statActiveProjects").textContent =
                stats.active_projects;

            document.getElementById("statTasksCompleted").textContent =
                stats.tasks_completed;

            document.getElementById("statTasksInProgress").textContent =
                stats.tasks_in_progress;

            document.getElementById("statUpcomingDeadlines").textContent =
                stats.upcoming_deadlines;
        } catch (error) {
            console.error("Dashboard statistics failed:", error);
        }
    }
    async function loadProjects() {
        if (!projectGrid) return;

        try {
            const response = await fetch("/api/projects.php", {
                headers: { Accept: "application/json" }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const result = await response.json();

            if (!result.success || !Array.isArray(result.data)) {
                throw new Error("Invalid API response");
            }

            projectGrid.replaceChildren();

            if (result.data.length === 0) {
                const empty = document.createElement("div");
                empty.className = "project-empty";

                const icon = document.createElement("i");
                icon.className = "bi bi-folder2-open";

                const heading = document.createElement("h3");
                heading.textContent = "No projects yet";

                const description = document.createElement("p");
                description.textContent = "Create your first project to get started.";

                empty.append(icon, heading, description);
                projectGrid.appendChild(empty);
                return;
            }

            const colors = ["purple", "orange", "blue", "green"];
            const icons = [
                "bi-window-stack",
                "bi-cpu",
                "bi-database",
                "bi-kanban"
            ];

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
        } catch (error) {
            console.error("Could not load projects:", error);

            projectGrid.replaceChildren();
            const message = document.createElement("p");
            message.className = "project-loading";
            message.textContent = "Projects couldn't be loaded. Please refresh to try again.";
            projectGrid.appendChild(message);
        }
    }

    if (createProjectButton) {
        createProjectButton.addEventListener("click", () => {
            if (modal) {
                if (projectError) {
                    projectError.hidden = true;
                    projectError.textContent = "";
                }
                modal.show();
            } else {
                showNotification("The project form couldn't be opened. Refresh the page and try again.");
            }
        });
    }

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
    if (projectForm) {
        projectForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            if (projectError) {
                projectError.hidden = true;
                projectError.textContent = "";
            }

            const formData = new FormData(projectForm);
            const payload = {
                name: String(formData.get("name") || "").trim(),
                description: String(formData.get("description") || "").trim(),
                status: String(formData.get("status") || "planning")
            };

            if (!payload.name) {
                if (projectError) {
                    projectError.textContent = "Please enter a project name.";
                    projectError.hidden = false;
                }
                return;
            }

            if (saveProjectButton) {
                saveProjectButton.disabled = true;
                saveProjectButton.textContent = "Saving...";
            }

            try {
                const response = await fetch("/api/create-project.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.error || "Unable to create project.");
                }

                projectForm.reset();
                modal?.hide();
                showNotification("Project created successfully.");
                await loadProjects();
            } catch (error) {
                if (projectError) {
                    projectError.textContent = error.message || "Something went wrong.";
                    projectError.hidden = false;
                }
            } finally {
                if (saveProjectButton) {
                    saveProjectButton.disabled = false;
                    saveProjectButton.textContent = "Create project";
                }
            }
        });
    }

    const taskList = document.getElementById("taskList");
    const taskFeedback = document.getElementById("taskFeedback");

    async function loadTasks() {
        if (!taskList) return;
        taskList.innerHTML = `<p class="task-feedback">Loading tasks...</p>`;
        try {
            const response = await fetch("/api/tasks.php");
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.error || "Could not load tasks.");
            const tasks = result.data || [];
            if (!tasks.length) {
                taskList.innerHTML = `<p class="task-feedback">No tasks yet. Create a task to get started.</p>`;
                if (taskFeedback) taskFeedback.textContent = "0 tasks";
                return;
            }
            const priorityClasses = { high: "priority-high", medium: "priority-medium", low: "priority-low" };
            const escapeHTML = (value) => String(value ?? "").replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;" }[c]));
            taskList.innerHTML = tasks.map((task) => {
                const dueDate = task.due_date ? new Date(`${task.due_date}T00:00:00`).toLocaleDateString("en-IN", { day: "numeric", month: "short" }) : "No due date";
                const completed = task.status === "completed";
                const priority = String(task.priority || "medium");
                                return `<div class="task-row">
                    <input class="form-check-input task-checkbox" type="checkbox" data-task-id="${Number(task.id)}" ${completed ? "checked" : ""}>
                    <span class="task-content"><strong>${escapeHTML(task.title)}</strong><small><span class="task-project-dot dot-blue"></span> ${escapeHTML(task.project_name)}</small></span>
                    <span class="task-priority ${priorityClasses[priority] || "priority-medium"}">${escapeHTML(priority.charAt(0).toUpperCase() + priority.slice(1))}</span>
                    <span class="task-date">${escapeHTML(dueDate)}</span>
                    <button type="button" class="btn btn-sm btn-outline-primary edit-task-button" data-task-id="${Number(task.id)}">Edit</button>
                </div>`;
      
            }).join("");
            const completedCount = tasks.filter((task) => task.status === "completed").length;
            if (taskFeedback) taskFeedback.textContent = `${completedCount} of ${tasks.length} tasks completed`;
            taskList.querySelectorAll(".task-checkbox").forEach((checkbox) => {
                checkbox.addEventListener("change", async () => {
                    const previousChecked = !checkbox.checked;
                    const taskId = Number(checkbox.dataset.taskId);
                    const newStatus = checkbox.checked ? "completed" : "todo";

                    checkbox.disabled = true;

                    try {
                        const response = await fetch("/api/update-task.php", {
                            method: "PATCH",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json"
                            },
                            body: JSON.stringify({
                                id: taskId,
                                status: newStatus
                            })
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.error || "Could not update task.");
                        }

                        await loadTasks();
                        showNotification(
                            newStatus === "completed"
                                ? "Task completed!"
                                : "Task marked as incomplete."
                        );
                    } catch (error) {
                        checkbox.checked = previousChecked;
                        showNotification(error.message || "Unable to update task.");
                    } finally {
                        checkbox.disabled = false;
                    }
                });
            });

        } catch (error) {
            taskList.innerHTML = `<p class="task-feedback">Unable to load tasks. Please refresh the page.</p>`;
            console.error("Task loading failed:", error);
        }
    }

           async function loadUpcomingTasks() {
        const upcomingList = document.getElementById("upcomingTaskList");
        if (!upcomingList) return;

        try {
            const response = await fetch("/api/tasks.php");
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error("Could not load upcoming tasks.");
            }

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const upcomingTasks = (result.data || [])
                .filter(task => {
                    if (!task.due_date || task.status === "completed") {
                        return false;
                    }

                    const dueDate = new Date(task.due_date + "T00:00:00");
                    const daysAway = (dueDate - today) / 86400000;

                    return daysAway >= 0 && daysAway <= 7;
                })
                .sort((a, b) => a.due_date.localeCompare(b.due_date));

            if (!upcomingTasks.length) {
                upcomingList.innerHTML =
                    `<p class="task-feedback">No upcoming tasks in the next 7 days.</p>`;
                return;
            }

            const escapeHTML = value =>
                String(value ?? "").replace(/[&<>"']/g, char => ({
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#39;"
                })[char]);

            upcomingList.innerHTML = upcomingTasks.map(task => {
                const date = new Date(task.due_date + "T00:00:00");
                const day = date.toLocaleDateString("en-IN", {
                    day: "2-digit"
                });
                const month = date.toLocaleDateString("en-IN", {
                    month: "short"
                }).toUpperCase();

                const dotClass = {
                    high: "event-orange",
                    medium: "event-purple",
                    low: "event-green"
                }[task.priority] || "event-purple";

                return `
                    <div class="upcoming-item">
                        <div class="upcoming-date">
                            <strong>${day}</strong>
                            <span>${month}</span>
                        </div>
                        <div class="upcoming-info">
                            <strong>${escapeHTML(task.title)}</strong>
                            <span>${escapeHTML(task.project_name)}</span>
                        </div>
                        <span class="event-dot ${dotClass}"></span>
                    </div>
                `;
            }).join("");
        } catch (error) {
            upcomingList.innerHTML =
                `<p class="task-feedback">Unable to load upcoming tasks.</p>`;
            console.error("Upcoming tasks failed:", error);
        }
    }
 loadProjects();
    loadTasks();
    loadDashboardStats();
    loadUpcomingTasks();

        // Edit Task form
    const editTaskModalElement = document.getElementById("editTaskModal");
    const editTaskModal = editTaskModalElement && window.bootstrap
        ? new bootstrap.Modal(editTaskModalElement)
        : null;

    const editTaskForm = document.getElementById("editTaskForm");
    const editTaskError = document.getElementById("editTaskError");
    const saveEditedTaskButton = document.getElementById("saveEditedTaskButton");

    const editTaskId = document.getElementById("editTaskId");
    const editTaskTitle = document.getElementById("editTaskTitle");
    const editTaskDescription = document.getElementById("editTaskDescription");
    const editTaskPriority = document.getElementById("editTaskPriority");
    const editTaskStatus = document.getElementById("editTaskStatus");
    const editTaskDueDate = document.getElementById("editTaskDueDate");

    taskList.addEventListener("click", async (event) => {
        const button = event.target.closest(".edit-task-button");
        if (!button) return;

        const taskId = Number(button.dataset.taskId);
        button.disabled = true;

        try {
            const response = await fetch("/api/tasks.php", {
                headers: { Accept: "application/json" }
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.error || "Could not load task details.");
            }

            const task = (result.data || []).find(
                (item) => Number(item.id) === taskId
            );

            if (!task) {
                throw new Error("Task not found. Refresh the page and try again.");
            }

            editTaskId.value = String(task.id);
            editTaskTitle.value = task.title || "";
            editTaskDescription.value = task.description || "";
            editTaskPriority.value = task.priority || "medium";
            editTaskStatus.value = task.status || "todo";
            editTaskDueDate.value = task.due_date || "";

            editTaskError.hidden = true;
            editTaskError.textContent = "";

            if (!editTaskModal) {
                throw new Error("The edit form could not be opened.");
            }

            editTaskModal.show();
        } catch (error) {
            showNotification(error.message || "Unable to open the edit form.");
        } finally {
            button.disabled = false;
        }
    });

    if (editTaskForm) {
        editTaskForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            editTaskError.hidden = true;
            editTaskError.textContent = "";

            const payload = {
                id: Number(editTaskId.value),
                title: editTaskTitle.value.trim(),
                description: editTaskDescription.value.trim(),
                priority: editTaskPriority.value,
                status: editTaskStatus.value,
                due_date: editTaskDueDate.value
            };

            if (!payload.id || !payload.title) {
                editTaskError.textContent = "Task title is required.";
                editTaskError.hidden = false;
                return;
            }

            saveEditedTaskButton.disabled = true;
            saveEditedTaskButton.textContent = "Saving...";

            try {
                const response = await fetch("/api/update-task.php", {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.error || "Could not save task changes.");
                }

                editTaskModal.hide();
                await loadTasks();
                showNotification("Task updated successfully.");
            } catch (error) {
                editTaskError.textContent =
                    error.message || "Unable to update task.";
                editTaskError.hidden = false;
            } finally {
                saveEditedTaskButton.disabled = false;
                saveEditedTaskButton.textContent = "Save changes";
            }
        });
    }
// Create Task form
    const createTaskButton = document.getElementById("createTaskButton");
    const createTaskModalElement = document.getElementById("createTaskModal");
    const createTaskModal = createTaskModalElement && window.bootstrap
        ? new bootstrap.Modal(createTaskModalElement)
        : null;
    const createTaskForm = document.getElementById("createTaskForm");
    const taskProjectSelect = document.getElementById("taskProjectId");
    const createTaskError = document.getElementById("createTaskError");
    const saveTaskButton = document.getElementById("saveTaskButton");

    async function loadTaskProjects() {
        if (!taskProjectSelect) return;

        try {
            const response = await fetch("/api/projects.php", {
                headers: { Accept: "application/json" }
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error("Unable to load projects.");
            }

            taskProjectSelect.replaceChildren();

            if (!result.data.length) {
                taskProjectSelect.add(new Option("Create a project first", ""));
                taskProjectSelect.disabled = true;
                return;
            }

            taskProjectSelect.disabled = false;
            taskProjectSelect.add(new Option("Select a project", ""));

            result.data.forEach((project) => {
                taskProjectSelect.add(
                    new Option(project.name, String(project.id))
                );
            });
        } catch (error) {
            taskProjectSelect.replaceChildren(
                new Option("Could not load projects", "")
            );
            taskProjectSelect.disabled = true;
            console.error("Could not load task projects:", error);
        }
    }

    if (createTaskButton) {
        createTaskButton.addEventListener("click", async () => {
            if (!createTaskModal) {
                showNotification("The task form could not be opened.");
                return;
            }

            if (createTaskError) {
                createTaskError.hidden = true;
                createTaskError.textContent = "";
            }

            await loadTaskProjects();
            createTaskModal.show();
        });
    }

    if (createTaskForm) {
        createTaskForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            if (createTaskError) {
                createTaskError.hidden = true;
                createTaskError.textContent = "";
            }

            const formData = new FormData(createTaskForm);
            const payload = {
                project_id: Number(formData.get("project_id")),
                title: String(formData.get("title") || "").trim(),
                description: String(formData.get("description") || "").trim(),
                priority: String(formData.get("priority") || "medium"),
                status: String(formData.get("status") || "todo"),
                due_date: String(formData.get("due_date") || "")
            };

            if (!payload.project_id || !payload.title) {
                if (createTaskError) {
                    createTaskError.textContent =
                        "Select a project and enter a task title.";
                    createTaskError.hidden = false;
                }
                return;
            }

            if (saveTaskButton) {
                saveTaskButton.disabled = true;
                saveTaskButton.textContent = "Creating...";
            }

            try {
                const response = await fetch("/api/create-task.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.error || "Unable to create task.");
                }

                createTaskForm.reset();
                createTaskModal?.hide();

                await loadTasks();
                showNotification("Task created successfully.");
            } catch (error) {
                if (createTaskError) {
                    createTaskError.textContent =
                        error.message || "Unable to create task.";
                    createTaskError.hidden = false;
                }
            } finally {
                if (saveTaskButton) {
                    saveTaskButton.disabled = false;
                    saveTaskButton.textContent = "Create task";
                }
            }
        });
    }

    console.log("CampusFlow dashboard JavaScript loaded successfully.");
});
