

function button_loading(form) {
    const loadingButtons = document.querySelectorAll('.loading-btn');
    const loader = document.querySelectorAll('.coin');

    console.log('Form submitting to:', form.action);

    loader.forEach(load => {
        load.style.display = 'inline-block';
    });

    setTimeout(() => {
        loadingButtons.forEach(button => {
            button.disabled = true;
            //setTimeout(() => form.submit(), 50);
        });
    }, 0);
    

    console.log('Submitting now...');
    return true;
}
