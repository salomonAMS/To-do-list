<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Include database configuration
require_once "config/database.php";

// Define variables
$task = "";
$task_err = "";
$success_msg = "";

// Process task operations
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Check which action is requested
    if(isset($_POST["action"])){
        // Add new task
        if($_POST["action"] == "add"){
            // Validate task
            if(empty(trim($_POST["task"]))){
                $task_err = "Please enter a task.";
            } else{
                $task = trim($_POST["task"]);
                $due_time = !empty($_POST["due_time"]) ? $_POST["due_time"] : null;
                
                // Prepare an insert statement
                $sql = "INSERT INTO tasks (user_id, task, due_time) VALUES (?, ?, ?)";
                
                if($stmt = mysqli_prepare($conn, $sql)){
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt, "iss", $param_user_id, $param_task, $param_due_time);
                    
                    // Set parameters
                    $param_user_id = $_SESSION["id"];
                    $param_task = $task;
                    $param_due_time = $due_time;
                    
                    // Attempt to execute the prepared statement
                    if(mysqli_stmt_execute($stmt)){
                        $success_msg = "Task added successfully!";
                        $task = ""; // Clear the input field
                    } else{
                        echo "Oops! Something went wrong. Please try again later.";
                    }

                    // Close statement
                    mysqli_stmt_close($stmt);
                }
            }
        }
        // Update task status (complete/incomplete)
        elseif($_POST["action"] == "update_status" && isset($_POST["task_id"]) && isset($_POST["status"])){
            $task_id = $_POST["task_id"];
            $status = $_POST["status"];
            $completed_at = ($status == 1) ? date('Y-m-d H:i:s') : null;
            
            // Prepare an update statement
            $sql = "UPDATE tasks SET status = ?, completed_at = ? WHERE id = ? AND user_id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "isii", $param_status, $param_completed_at, $param_task_id, $param_user_id);
                
                // Set parameters
                $param_status = $status;
                $param_completed_at = $completed_at;
                $param_task_id = $task_id;
                $param_user_id = $_SESSION["id"];
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "Task status updated!";
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }
        }
        // Edit task
        elseif($_POST["action"] == "edit" && isset($_POST["task_id"]) && isset($_POST["task_text"])){
            $task_id = $_POST["task_id"];
            $task_text = trim($_POST["task_text"]);
            $due_time = !empty($_POST["due_time"]) ? $_POST["due_time"] : null;
            
            if(empty($task_text)){
                $task_err = "Task cannot be empty.";
            } else {
                // Prepare an update statement
                $sql = "UPDATE tasks SET task = ?, due_time = ? WHERE id = ? AND user_id = ?";
                
                if($stmt = mysqli_prepare($conn, $sql)){
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt, "ssii", $param_task, $param_due_time, $param_task_id, $param_user_id);
                    
                    // Set parameters
                    $param_task = $task_text;
                    $param_due_time = $due_time;
                    $param_task_id = $task_id;
                    $param_user_id = $_SESSION["id"];
                    
                    // Attempt to execute the prepared statement
                    if(mysqli_stmt_execute($stmt)){
                        $success_msg = "Task updated successfully!";
                    } else{
                        echo "Oops! Something went wrong. Please try again later.";
                    }

                    // Close statement
                    mysqli_stmt_close($stmt);
                }
            }
        }
        // Delete task
        elseif($_POST["action"] == "delete" && isset($_POST["task_id"])){
            $task_id = $_POST["task_id"];
            
            // Prepare a delete statement
            $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "ii", $param_task_id, $param_user_id);
                
                // Set parameters
                $param_task_id = $task_id;
                $param_user_id = $_SESSION["id"];
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "Task deleted successfully!";
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }
        }
        // Delete from history
        elseif($_POST["action"] == "delete_from_history" && isset($_POST["task_id"])){
            $task_id = $_POST["task_id"];
            
            // First, move to history if it's a completed task
            $sql = "SELECT task, completed_at FROM tasks WHERE id = ? AND user_id = ? AND status = 1";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "ii", $task_id, $_SESSION["id"]);
                if(mysqli_stmt_execute($stmt)){
                    $result = mysqli_stmt_get_result($stmt);
                    if($row = mysqli_fetch_assoc($result)){
                        // Insert into history
                        $history_sql = "INSERT INTO task_history (user_id, task, completed_at) VALUES (?, ?, ?)";
                        if($history_stmt = mysqli_prepare($conn, $history_sql)){
                            mysqli_stmt_bind_param($history_stmt, "iss", $_SESSION["id"], $row['task'], $row['completed_at']);
                            mysqli_stmt_execute($history_stmt);
                            mysqli_stmt_close($history_stmt);
                        }
                    }
                }
                mysqli_stmt_close($stmt);
            }
            
            // Then delete from tasks
            $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "ii", $task_id, $_SESSION["id"]);
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "Task moved to history!";
                }
                mysqli_stmt_close($stmt);
            }
        }
        // Permanently delete from history
        elseif($_POST["action"] == "permanent_delete" && isset($_POST["history_id"])){
            $history_id = $_POST["history_id"];
            
            $sql = "DELETE FROM task_history WHERE id = ? AND user_id = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "ii", $history_id, $_SESSION["id"]);
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "Task permanently deleted!";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Fetch all active tasks for the current user
$tasks = [];
$sql = "SELECT id, task, status, due_time, created_at FROM tasks WHERE user_id = ? ORDER BY created_at DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $param_user_id);
    $param_user_id = $_SESSION["id"];
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $tasks[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Fetch completed tasks for dashboard
$completed_tasks = [];
$sql = "SELECT id, task, completed_at, due_time FROM tasks WHERE user_id = ? AND status = 1 ORDER BY completed_at DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $param_user_id);
    $param_user_id = $_SESSION["id"];
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $completed_tasks[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Fetch task history
$task_history = [];
$sql = "SELECT id, task, completed_at, deleted_at FROM task_history WHERE user_id = ? ORDER BY deleted_at DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $param_user_id);
    $param_user_id = $_SESSION["id"];
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $task_history[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Get statistics for dashboard
$stats = [
    'total_tasks' => count($tasks),
    'completed_tasks' => count($completed_tasks),
    'pending_tasks' => count(array_filter($tasks, function($task) { return $task['status'] == 0; })),
    'overdue_tasks' => 0
];

// Count overdue tasks
foreach($tasks as $task) {
    if($task['status'] == 0 && $task['due_time'] && strtotime($task['due_time']) < time()) {
        $stats['overdue_tasks']++;
    }
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - To-Do List App</title>
    <link rel="stylesheet" href="css/style-task.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>My To-Do List</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?></span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <?php 
        if(!empty($success_msg)){
            echo '<div class="alert alert-success">' . $success_msg . '</div>';
        }
        ?>
        
        <!-- Navigation Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab(event, 'dashboard')">
                <i class="fas fa-chart-pie"></i> Dashboard
            </button>
            <button class="tab-btn" onclick="openTab(event, 'tasks')">
                <i class="fas fa-tasks"></i> Mes Tâches
            </button>
        </div>
        
        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content active">
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-list"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_tasks']; ?></h3>
                        <p>Total des tâches</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['completed_tasks']; ?></h3>
                        <p>Tâches terminées</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_tasks']; ?></h3>
                        <p>Tâches en attente</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['overdue_tasks']; ?></h3>
                        <p>Tâches en retard</p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="dashboard-section">
                    <h3><i class="fas fa-history"></i> Tâches Terminées Récemment</h3>
                    <div class="completed-tasks-list">
                        <?php if(count($completed_tasks) > 0): ?>
                            <?php foreach(array_slice($completed_tasks, 0, 5) as $task): ?>
                                <div class="completed-task-item">
                                    <div class="task-info">
                                        <span class="task-text"><?php echo htmlspecialchars($task['task']); ?></span>
                                        <span class="completion-date">
                                            Terminée le <?php echo date('d/m/Y à H:i', strtotime($task['completed_at'])); ?>
                                        </span>
                                    </div>
                                    <button class="btn-delete-small" onclick="deleteFromHistory(<?php echo $task['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-data">Aucune tâche terminée récemment.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="dashboard-section">
                    <h3><i class="fas fa-archive"></i> Historique des Tâches Supprimées</h3>
                    <div class="history-list">
                        <?php if(count($task_history) > 0): ?>
                            <?php foreach(array_slice($task_history, 0, 5) as $history): ?>
                                <div class="history-item">
                                    <div class="task-info">
                                        <span class="task-text"><?php echo htmlspecialchars($history['task']); ?></span>
                                        <span class="deletion-date">
                                            Supprimée le <?php echo date('d/m/Y à H:i', strtotime($history['deleted_at'])); ?>
                                        </span>
                                    </div>
                                    <button class="btn-delete-small" onclick="permanentDelete(<?php echo $history['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-data">Aucune tâche dans l'historique.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tasks Tab -->
        <div id="tasks" class="tab-content">
            <div class="task-form">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <input type="text" name="task" class="form-control <?php echo (!empty($task_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $task; ?>" placeholder="Ajouter une nouvelle tâche...">
                        <input type="datetime-local" name="due_time" class="form-control datetime-input" title="Heure d'échéance (optionnel)">
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                    <span class="invalid-feedback"><?php echo $task_err; ?></span>
                </form>
            </div>
            
            <div class="task-list">
                <?php if(count($tasks) > 0): ?>
                    <ul>
                        <?php foreach($tasks as $task): ?>
                            <li class="task-item <?php echo ($task['status'] == 1) ? 'completed' : ''; ?> <?php echo ($task['status'] == 0 && $task['due_time'] && strtotime($task['due_time']) < time()) ? 'overdue' : ''; ?>" data-id="<?php echo $task['id']; ?>">
                                <div class="task-content">
                                    <input type="checkbox" class="task-status" <?php echo ($task['status'] == 1) ? 'checked' : ''; ?> onchange="updateTaskStatus(<?php echo $task['id']; ?>, this.checked ? 1 : 0)">
                                    <div class="task-details">
                                        <span class="task-text"><?php echo htmlspecialchars($task['task']); ?></span>
                                        <?php if($task['due_time']): ?>
                                            <span class="due-time">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($task['due_time'])); ?>
                                                <?php if($task['status'] == 0 && strtotime($task['due_time']) < time()): ?>
                                                    <span class="overdue-label">En retard</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="task-date">Créée le <?php echo date('d/m/Y', strtotime($task['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <button class="btn-edit" onclick="editTask(<?php echo $task['id']; ?>, '<?php echo addslashes($task['task']); ?>', '<?php echo $task['due_time'] ? date('Y-m-d\TH:i', strtotime($task['due_time'])) : ''; ?>')"><i class="fas fa-edit"></i></button>
                                    <button class="btn-delete" onclick="deleteTask(<?php echo $task['id']; ?>)"><i class="fas fa-trash"></i></button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-tasks">Aucune tâche trouvée. Ajoutez une nouvelle tâche pour commencer !</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Task Modal -->
    <div id="editTaskModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Modifier la Tâche</h2>
            <form id="editTaskForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="task_id" id="edit_task_id">
                <div class="form-group">
                    <label for="edit_task_text">Tâche :</label>
                    <input type="text" name="task_text" id="edit_task_text" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_due_time">Heure d'échéance :</label>
                    <input type="datetime-local" name="due_time" id="edit_due_time" class="form-control">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Hidden Forms -->
    <form id="deleteTaskForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="task_id" id="delete_task_id">
    </form>
    
    <form id="deleteFromHistoryForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: none;">
        <input type="hidden" name="action" value="delete_from_history">
        <input type="hidden" name="task_id" id="delete_from_history_task_id">
    </form>
    
    <form id="permanentDeleteForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: none;">
        <input type="hidden" name="action" value="permanent_delete">
        <input type="hidden" name="history_id" id="permanent_delete_history_id">
    </form>
    
    <form id="updateStatusForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="task_id" id="status_task_id">
        <input type="hidden" name="status" id="status_value">
    </form>
    
    <script>
        // Tab functionality
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        
        // Modal functionality
        const modal = document.getElementById("editTaskModal");
        const span = document.getElementsByClassName("close")[0];
        
        function editTask(taskId, taskText, dueTime) {
            document.getElementById("edit_task_id").value = taskId;
            document.getElementById("edit_task_text").value = taskText;
            document.getElementById("edit_due_time").value = dueTime;
            modal.style.display = "block";
        }
        
        function deleteTask(taskId) {
            if(confirm("Êtes-vous sûr de vouloir supprimer cette tâche ?")) {
                document.getElementById("delete_task_id").value = taskId;
                document.getElementById("deleteTaskForm").submit();
            }
        }
        
        function deleteFromHistory(taskId) {
            if(confirm("Voulez-vous déplacer cette tâche vers l'historique ?")) {
                document.getElementById("delete_from_history_task_id").value = taskId;
                document.getElementById("deleteFromHistoryForm").submit();
            }
        }
        
        function permanentDelete(historyId) {
            if(confirm("Êtes-vous sûr de vouloir supprimer définitivement cette tâche ?")) {
                document.getElementById("permanent_delete_history_id").value = historyId;
                document.getElementById("permanentDeleteForm").submit();
            }
        }
        
        function updateTaskStatus(taskId, status) {
            document.getElementById("status_task_id").value = taskId;
            document.getElementById("status_value").value = status;
            document.getElementById("updateStatusForm").submit();
        }
        
        // Close modal
        span.onclick = function() {
            modal.style.display = "none";
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        
        // Notification system for reminders
        function checkReminders() {
            const tasks = <?php echo json_encode($tasks); ?>;
            const now = new Date().getTime();
            
            tasks.forEach(task => {
                if (task.status == 0 && task.due_time) {
                    const dueTime = new Date(task.due_time).getTime();
                    const timeDiff = dueTime - now;
                    
                    // Notify 1 hour before due time
                    if (timeDiff > 0 && timeDiff <= 3600000) { // 1 hour in milliseconds
                        if (Notification.permission === "granted") {
                            new Notification("Rappel de tâche", {
                                body: `La tâche "${task.task}" est due dans moins d'une heure !`,
                                icon: "https://cdn-icons-png.flaticon.com/512/1827/1827422.png"
                            });
                        }
                    }
                    
                    // Notify if overdue
                    if (timeDiff < 0) {
                        if (Notification.permission === "granted") {
                            new Notification("Tâche en retard", {
                                body: `La tâche "${task.task}" est en retard !`,
                                icon: "https://cdn-icons-png.flaticon.com/512/564/564619.png"
                            });
                        }
                    }
                }
            });
        }
        
        // Request notification permission
        if ("Notification" in window) {
            if (Notification.permission === "default") {
                Notification.requestPermission();
            }
        }
        
        // Check reminders every 30 minutes
        setInterval(checkReminders, 1800000);
        
        // Check reminders on page load
        checkReminders();
    </script>
</body>
</html>