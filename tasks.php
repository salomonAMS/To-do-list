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
                
                // Prepare an insert statement
                $sql = "INSERT INTO tasks (user_id, task) VALUES (?, ?)";
                
                if($stmt = mysqli_prepare($conn, $sql)){
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt, "is", $param_user_id, $param_task);
                    
                    // Set parameters
                    $param_user_id = $_SESSION["id"];
                    $param_task = $task;
                    
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
            
            // Prepare an update statement
            $sql = "UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "iii", $param_status, $param_task_id, $param_user_id);
                
                // Set parameters
                $param_status = $status;
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
            
            if(empty($task_text)){
                $task_err = "Task cannot be empty.";
            } else {
                // Prepare an update statement
                $sql = "UPDATE tasks SET task = ? WHERE id = ? AND user_id = ?";
                
                if($stmt = mysqli_prepare($conn, $sql)){
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt, "sii", $param_task, $param_task_id, $param_user_id);
                    
                    // Set parameters
                    $param_task = $task_text;
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
    }
}

// Fetch all tasks for the current user
$tasks = [];
$sql = "SELECT id, task, status, created_at FROM tasks WHERE user_id = ? ORDER BY created_at DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "i", $param_user_id);
    
    // Set parameters
    $param_user_id = $_SESSION["id"];
    
    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt)){
        // Store result
        $result = mysqli_stmt_get_result($stmt);
        
        // Fetch all tasks
        while($row = mysqli_fetch_assoc($result)){
            $tasks[] = $row;
        }
    } else{
        echo "Oops! Something went wrong. Please try again later.";
    }

    // Close statement
    mysqli_stmt_close($stmt);
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
        
        <div class="task-form">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <input type="text" name="task" class="form-control <?php echo (!empty($task_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $task; ?>" placeholder="Add a new task...">
                    <button type="submit" class="btn btn-primary">Add Task</button>
                </div>
                <span class="invalid-feedback"><?php echo $task_err; ?></span>
            </form>
        </div>
        
        <div class="task-list">
            <?php if(count($tasks) > 0): ?>
                <ul>
                    <?php foreach($tasks as $task): ?>
                        <li class="task-item <?php echo ($task['status'] == 1) ? 'completed' : ''; ?>" data-id="<?php echo $task['id']; ?>">
                            <div class="task-content">
                                <input type="checkbox" class="task-status" <?php echo ($task['status'] == 1) ? 'checked' : ''; ?> onchange="updateTaskStatus(<?php echo $task['id']; ?>, this.checked ? 1 : 0)">
                                <span class="task-text"><?php echo htmlspecialchars($task['task']); ?></span>
                                <span class="task-date"><?php echo date('M d, Y', strtotime($task['created_at'])); ?></span>
                            </div>
                            <div class="task-actions">
                                <button class="btn-edit" onclick="editTask(<?php echo $task['id']; ?>, '<?php echo addslashes($task['task']); ?>')"><i class="fas fa-edit"></i></button>
                                <button class="btn-delete" onclick="deleteTask(<?php echo $task['id']; ?>)"><i class="fas fa-trash"></i></button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="no-tasks">No tasks found. Add a new task to get started!</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Edit Task Modal -->
    <div id="editTaskModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Task</h2>
            <form id="editTaskForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="task_id" id="edit_task_id">
                <div class="form-group">
                    <input type="text" name="task_text" id="edit_task_text" class="form-control" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Task Form (Hidden) -->
    <form id="deleteTaskForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="task_id" id="delete_task_id">
    </form>
    
    <!-- Update Status Form (Hidden) -->
    <form id="updateStatusForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="task_id" id="status_task_id">
        <input type="hidden" name="status" id="status_value">
    </form>
    
    <script>
        // Get the modal
        const modal = document.getElementById("editTaskModal");
        const span = document.getElementsByClassName("close")[0];
        
        // Edit task function
        function editTask(taskId, taskText) {
            document.getElementById("edit_task_id").value = taskId;
            document.getElementById("edit_task_text").value = taskText;
            modal.style.display = "block";
        }
        
        // Delete task function
        function deleteTask(taskId) {
            if(confirm("Are you sure you want to delete this task?")) {
                document.getElementById("delete_task_id").value = taskId;
                document.getElementById("deleteTaskForm").submit();
            }
        }
        
        // Update task status function
        function updateTaskStatus(taskId, status) {
            document.getElementById("status_task_id").value = taskId;
            document.getElementById("status_value").value = status;
            document.getElementById("updateStatusForm").submit();
        }
        
        // Close the modal when clicking on (x)
        span.onclick = function() {
            modal.style.display = "none";
        }
        
        // Close the modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>