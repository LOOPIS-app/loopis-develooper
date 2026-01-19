

function button_loading(form) {
    const loadingButtons = document.querySelectorAll('.loading-btn');
    const loader = document.getElementById('loader');

    console.log('Form submitting to:', form.action);
    loader.style.display = 'inline-block';

    setTimeout(() => {
        loadingButtons.forEach(button => {
            button.disabled = true;
            //setTimeout(() => form.submit(), 50);
        });
    }, 0);
    

    console.log('Submitting now...');
    return true;
}
