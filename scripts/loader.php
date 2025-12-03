<?php
function activate_loader($action_btn, $loader, $form) {

    ?><script>
        let action_value = <?php echo json_encode($action_btn); ?>;
        let loader_value = <?php echo json_encode($loader); ?>;
        let form_value = <?php echo json_encode($form); ?>;

        const bunchActionButton = document.querySelectorAll(action_value);
        const loader = document.getElementById(loader_value);
        const form = document.getElementById(form_value);

        console.log('Action running');
        console.log('Action: ',action_value);

        bunchActionButton.forEach(button => {
            button.addEventListener('click', async () => {
                // Disable the button
                // and prevent further clicks
                if (bunchActionButton.disabled) {
                    return;
                };

                loader.style.display = 'inline-block';

                buttons.forEach(b => b.disabled = true);
                // allow browser to render the loader, then submit
                setTimeout(() => form.submit(), 50);
            }, { once: true });
        });
    </script><?php
}
