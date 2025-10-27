<?php
/**
 * Questions Management Page - JavaScript Authentication Version
 */

// Simple PHP check - if no session, let JavaScript handle it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Management - Admin Panel</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <script src="js/utils.js"></script>
</head>
<body>
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Checking admin access...</div>
    </div>

    <div id="adminContent" style="display: none;">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content-area">
                <!-- Filters -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Question Management</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Subject</label>
                                    <select class="form-control" id="subjectFilter">
                                        <option value="">All Subjects</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Difficulty</label>
                                    <select class="form-control" id="difficultyFilter">
                                        <option value="">All Difficulties</option>
                                        <option value="easy">Easy</option>
                                        <option value="medium">Medium</option>
                                        <option value="hard">Hard</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <button class="btn btn-primary w-100" id="searchBtn">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <button class="btn btn-success w-100" id="addQuestionBtn">
                                        <i class="fas fa-plus"></i> Add Question
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions Grid -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Questions List</h3>
                        <div>
                            <span class="text-muted" id="questionsCount">Loading...</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="questionsGrid" class="questions-grid">
                            <div class="loading">
                                <div class="spinner"></div>
                                <p>Loading questions...</p>
                            </div>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-3" id="paginationContainer">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="accessDenied" style="display: none;" class="text-center p-5">
        <div class="card">
            <div class="card-body">
                <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                <h3>Access Denied</h3>
                <p>You don't have admin privileges to access this panel.</p>
                <a href="../pages/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <!-- Add/Edit Question Modal -->
    <div class="modal" id="questionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Question</h5>
                <button type="button" class="modal-close" onclick="closeQuestionModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="questionForm">
                    <input type="hidden" id="questionId">
                    
                    <div class="form-group">
                        <label class="form-label">Image URL</label>
                        <input type="url" class="form-control" id="imageUrl" required placeholder="https://example.com/image.jpg">
                        <small class="form-text text-muted">Enter the URL of the question image</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" required placeholder="e.g., Mathematics, Physics">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Difficulty</label>
                        <select class="form-control" id="difficulty" required>
                            <option value="">Select Difficulty</option>
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Correct Answer</label>
                        <select class="form-control" id="correctAnswer" required>
                            <option value="">Select Answer</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    
                    <!-- Image Preview -->
                    <div class="form-group" id="imagePreviewGroup" style="display: none;">
                        <label class="form-label">Image Preview</label>
                        <div class="image-preview">
                            <img id="imagePreview" src="" alt="Question Image" style="max-width: 100%; max-height: 300px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeQuestionModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveQuestion()">Save Question</button>
            </div>
        </div>
    </div>

    <script>

        class QuestionManagement {
            constructor() {
                // Use shared Supabase client from AdminUtils to avoid multiple instances
                this.supabase = window.adminUtils?.supabase || window.supabase.createClient(
                    'https://ratxqmbqzwbvfgsonlrd.supabase.co',
                    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJhdHhxbWJxendidmZnc29ubHJkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDQyMDI0NDAsImV4cCI6MjA1OTc3ODQ0MH0.HJ9nQbvVvVisvQb6HMVMlmQBVmW7Ie42Z6Afdwn8W2M',
                    {
                        auth: {
                            autoRefreshToken: true,
                            persistSession: true,
                            detectSessionInUrl: true
                        }
                    }
                );
                this.currentPage = 1;
                this.currentFilters = {};
                this.init();
            }

            async init() {
                try {
                    // Check if user is authenticated and is admin
                    const isAdmin = await this.checkAdminAccess();
                    
                    if (isAdmin) {
                        document.getElementById('loadingOverlay').style.display = 'none';
                        document.getElementById('adminContent').style.display = 'block';
                        this.setupEventListeners();
                        await this.loadSubjects();
                        this.loadQuestions();
                    } else {
                        document.getElementById('loadingOverlay').style.display = 'none';
                        document.getElementById('accessDenied').style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error initializing question management:', error);
                    document.getElementById('loadingOverlay').style.display = 'none';
                    document.getElementById('accessDenied').style.display = 'block';
                }
            }

            async checkAdminAccess() {
                try {
                    const { data: { session }, error } = await this.supabase.auth.getSession();
                    
                    if (error || !session) {
                        return false;
                    }

                    const { data: profile, error: profileError } = await this.supabase
                        .from('profiles')
                        .select('is_admin, username, full_name')
                        .eq('user_id', session.user.id)
                        .single();

                    if (profileError || !profile) {
                        return false;
                    }

                    window.adminProfile = profile;
                    return profile.is_admin === true;
                } catch (error) {
                    console.error('Error checking admin access:', error);
                    return false;
                }
            }

            setupEventListeners() {
                // Search button
                document.getElementById('searchBtn').addEventListener('click', () => {
                    this.currentPage = 1;
                    this.loadQuestions();
                });

                // Add question button
                document.getElementById('addQuestionBtn').addEventListener('click', () => {
                    this.openAddModal();
                });

                // Filter changes
                ['subjectFilter', 'difficultyFilter'].forEach(id => {
                    document.getElementById(id).addEventListener('change', () => {
                        this.currentPage = 1;
                        this.loadQuestions();
                    });
                });

                // Image URL preview
                document.getElementById('imageUrl').addEventListener('input', (e) => {
                    this.updateImagePreview(e.target.value);
                });
            }

            async loadSubjects() {
                try {
                    const response = await this.utils?.apiRequest('questions.php?action=subjects') || 
                                   await this.supabase.from('questions').select('subject').not('subject', 'is', null);
                    
                    const subjects = response.subjects || response.data?.map(q => q.subject).filter(Boolean) || [];
                    const uniqueSubjects = [...new Set(subjects)].sort();
                    
                    const subjectFilter = document.getElementById('subjectFilter');
                    subjectFilter.innerHTML = '<option value="">All Subjects</option>';
                    uniqueSubjects.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject;
                        option.textContent = subject;
                        subjectFilter.appendChild(option);
                    });
                } catch (error) {
                    console.error('Error loading subjects:', error);
                }
            }

            getCurrentFilters() {
                return {
                    subject: document.getElementById('subjectFilter').value,
                    difficulty: document.getElementById('difficultyFilter').value,
                    page: this.currentPage,
                    limit: 12
                };
            }

            async loadQuestions() {
                try {
                    this.currentFilters = this.getCurrentFilters();
                    
                    // Build Supabase query
                    let query = this.supabase.from('questions').select('*');
                    
                    // Apply filters
                    if (this.currentFilters.subject) {
                        query = query.eq('subject', this.currentFilters.subject);
                    }
                    
                    if (this.currentFilters.difficulty) {
                        query = query.eq('difficulty', this.currentFilters.difficulty);
                    }
                    
                    // Add ordering and pagination
                    query = query.order('created_at', { ascending: false });
                    query = query.range(
                        (this.currentPage - 1) * this.currentFilters.limit,
                        this.currentPage * this.currentFilters.limit - 1
                    );

                    const { data: questions, error } = await query;

                    if (error) {
                        throw error;
                    }

                    this.renderQuestionsGrid(questions || []);
                    this.updateQuestionsCount(questions ? questions.length : 0);

                } catch (error) {
                    console.error('Error loading questions:', error);
                    this.showAlert('Error loading questions', 'danger');
                }
            }

            renderQuestionsGrid(questions) {
                const grid = document.getElementById('questionsGrid');
                
                if (!questions || questions.length === 0) {
                    grid.innerHTML = `
                        <div class="text-center text-muted p-4">
                            <i class="fas fa-question-circle fa-3x mb-3"></i>
                            <p>No questions found</p>
                        </div>
                    `;
                    return;
                }

                const html = questions.map(question => `
                    <div class="question-card">
                        <div class="question-image">
                            <img src="${question.image_url}" alt="Question ${question.id}" 
                                 onerror="this.src='../img/default.png'" 
                                 onclick="questionManagement.viewQuestion('${question.id}')">
                        </div>
                        <div class="question-info">
                            <div class="question-meta">
                                <span class="badge badge-info">${question.subject || 'No Subject'}</span>
                                <span class="badge badge-${this.getDifficultyClass(question.difficulty)}">${question.difficulty}</span>
                            </div>
                            <div class="question-answer">
                                <strong>Answer: ${question.correct_answer}</strong>
                            </div>
                            <div class="question-actions">
                                <button class="btn btn-sm btn-primary" onclick="questionManagement.editQuestion('${question.id}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="questionManagement.deleteQuestion('${question.id}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');

                grid.innerHTML = html;
            }

            getDifficultyClass(difficulty) {
                const classes = {
                    'easy': 'success',
                    'medium': 'warning',
                    'hard': 'danger'
                };
                return classes[difficulty] || 'secondary';
            }

            updateQuestionsCount(total) {
                document.getElementById('questionsCount').textContent = `${total} questions found`;
            }

            updateImagePreview(url) {
                const previewGroup = document.getElementById('imagePreviewGroup');
                const previewImg = document.getElementById('imagePreview');
                
                if (url && url.startsWith('http')) {
                    previewImg.src = url;
                    previewGroup.style.display = 'block';
                } else {
                    previewGroup.style.display = 'none';
                }
            }

            openAddModal() {
                document.getElementById('modalTitle').textContent = 'Add New Question';
                document.getElementById('questionForm').reset();
                document.getElementById('imagePreviewGroup').style.display = 'none';
                document.getElementById('questionModal').classList.add('show');
            }

            async editQuestion(questionId) {
                try {
                    const response = await this.utils?.apiRequest(`questions.php?action=single&id=${questionId}`) ||
                                   await this.supabase.from('questions').select('*').eq('id', questionId).single();
                    
                    const question = response.question || response.data;

                    // Populate form
                    document.getElementById('modalTitle').textContent = 'Edit Question';
                    document.getElementById('questionId').value = questionId;
                    document.getElementById('imageUrl').value = question.image_url || '';
                    document.getElementById('subject').value = question.subject || '';
                    document.getElementById('difficulty').value = question.difficulty || '';
                    document.getElementById('correctAnswer').value = question.correct_answer || '';

                    // Show image preview
                    this.updateImagePreview(question.image_url);

                    // Show modal
                    document.getElementById('questionModal').classList.add('show');

                } catch (error) {
                    console.error('Error loading question:', error);
                    this.showAlert('Error loading question details', 'danger');
                }
            }

            async saveQuestion() {
                try {
                    const questionId = document.getElementById('questionId').value;
                    const data = {
                        image_url: document.getElementById('imageUrl').value,
                        subject: document.getElementById('subject').value,
                        difficulty: document.getElementById('difficulty').value,
                        correct_answer: document.getElementById('correctAnswer').value
                    };

                    if (questionId) {
                        // Update existing question
                        await this.utils?.apiRequest(`questions.php?id=${questionId}`, 'PATCH', data);
                        this.showAlert('Question updated successfully', 'success');
                    } else {
                        // Create new question
                        await this.utils?.apiRequest('questions.php', 'POST', data);
                        this.showAlert('Question created successfully', 'success');
                    }
                    
                    this.closeQuestionModal();
                    this.loadQuestions();

                } catch (error) {
                    console.error('Error saving question:', error);
                    this.showAlert('Error saving question', 'danger');
                }
            }

            async deleteQuestion(questionId) {
                this.utils?.showConfirm(
                    'Delete Question',
                    'Are you sure you want to delete this question? This action cannot be undone.',
                    async () => {
                        try {
                            await this.utils?.apiRequest(`questions.php?id=${questionId}`, 'DELETE');
                            this.showAlert('Question deleted successfully', 'success');
                            this.loadQuestions();
                        } catch (error) {
                            console.error('Error deleting question:', error);
                            this.showAlert('Error deleting question', 'danger');
                        }
                    }
                );
            }

            viewQuestion(questionId) {
                // Open question image in new tab
                const question = document.querySelector(`[onclick*="${questionId}"]`);
                if (question) {
                    const img = question.querySelector('img');
                    if (img) {
                        window.open(img.src, '_blank');
                    }
                }
            }

            closeQuestionModal() {
                document.getElementById('questionModal').classList.remove('show');
            }

            showAlert(message, type = 'info') {
                // Use AdminUtils if available, otherwise create simple alert
                if (this.utils?.showAlert) {
                    this.utils.showAlert(message, type);
                } else {
                    alert(message);
                }
            }
        }

        // Global functions for modal
        function closeQuestionModal() {
            window.questionManagement.closeQuestionModal();
        }

        function saveQuestion() {
            window.questionManagement.saveQuestion();
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            window.questionManagement = new QuestionManagement();
        });
    </script>
</body>
</html>