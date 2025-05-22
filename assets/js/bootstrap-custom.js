document.addEventListener('DOMContentLoaded', function() {
// Modal minimal implementation
    var modalElements = document.querySelectorAll('.modal');
    var modalInstances = {};

    modalElements.forEach(function(modalElement) {
        var modalId = modalElement.id;
        var closeButtons = modalElement.querySelectorAll('[data-bs-dismiss="modal"]');
        var backdrop = null;

// Create backdrop
        function createBackdrop() {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade';
            document.body.appendChild(backdrop);
            setTimeout(function() { backdrop.classList.add('show'); }, 10);
            backdrop.addEventListener('click', hideModal);
        }

// Show modal
        function showModal() {
            document.body.style.overflow = 'hidden';
            createBackdrop();
            modalElement.style.display = 'block';
            setTimeout(function() { modalElement.classList.add('show'); }, 10);
        }

// Hide modal
        function hideModal() {
            modalElement.classList.remove('show');
            setTimeout(function() {
                modalElement.style.display = 'none';
                if (backdrop) {
                    backdrop.classList.remove('show');
                    setTimeout(function() {
                        document.body.removeChild(backdrop);
                        backdrop = null;
                        document.body.style.overflow = '';
                    }, 150);
                }
            }, 150);
        }

// Register close buttons
        closeButtons.forEach(function(button) {
            button.addEventListener('click', hideModal);
        });

// Register escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalElement.classList.contains('show')) {
                hideModal();
            }
        });

// Store instance
        modalInstances[modalId] = {
            show: showModal,
            hide: hideModal
        };
    });

// Make instances available globally
    window.bootstrap = {
        Modal: {
            getInstance: function(element) {
                var modalId = typeof element === 'string' ? element : element.id;
                return modalInstances[modalId];
            }
        }
    };
});