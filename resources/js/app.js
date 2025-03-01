import * as bootstrap from 'bootstrap'
import 'bootstrap/dist/css/bootstrap.min.css'

// You can add any additional JavaScript initialization here
window.bootstrap = bootstrap;

document.addEventListener('livewire:initialized', () => {
    // Function to handle column visibility dropdown behavior
    const setupColumnVisibilityDropdown = () => {
        const dropdownToggle = document.getElementById('columnVisibilityDropdown');
        const dropdownMenu = dropdownToggle?.nextElementSibling;

        if (dropdownToggle && dropdownMenu) {
            // Add event listener to checkboxes within the dropdown
            dropdownMenu.querySelectorAll('.form-check-input').forEach(checkbox => {
                checkbox.addEventListener('click', (e) => {
                    // Prevent the dropdown from staying open
                    const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                    if (bsDropdown) {
                        bsDropdown.hide();
                    }
                });
            });
        }
    };

    // Run setup when Livewire initializes
    setupColumnVisibilityDropdown();

    // Re-run setup if Livewire updates the DOM
    document.addEventListener('livewire:update', setupColumnVisibilityDropdown);

    // Listen for column visibility changes
    Livewire.on('column-visibility-changed', (event) => {
        console.log(`Column ${event.column} visibility changed to ${event.visible}`);
    });

    // Listen for column reset
    Livewire.on('columns-reset', () => {
        console.log('Columns reset to default');
    });

    // Notification handling
    Livewire.on('notify', (event) => {
        const notificationContainer = document.getElementById('notification-container');
        
        if (notificationContainer) {
            // Create notification element
            const notification = document.createElement('div');
            notification.classList.add(
                'toast', 
                `toast-${event.type}`, 
                'align-items-center', 
                'text-bg-' + event.type, 
                'border-0', 
                'show'
            );
            notification.setAttribute('role', 'alert');
            notification.setAttribute('aria-live', 'assertive');
            notification.setAttribute('aria-atomic', 'true');
            
            // Notification content
            notification.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${event.message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            // Add to container
            notificationContainer.appendChild(notification);
            
            // Initialize Bootstrap Toast
            const toastInstance = new bootstrap.Toast(notification, {
                delay: 5000 // 5 seconds
            });
            toastInstance.show();
            
            // Remove toast after it's hidden
            notification.addEventListener('hidden.bs.toast', () => {
                notification.remove();
            });
        }
    });
});
