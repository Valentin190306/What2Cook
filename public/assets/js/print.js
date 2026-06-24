function printPlan(card) {
    document.body.classList.add('printing-plan');
    card.classList.add('printing');
    window.print();
}

function printList(card) {
    document.body.classList.add('printing-list');
    card.classList.add('printing');
    window.print();
}

window.addEventListener('afterprint', function () {
    document.body.classList.remove('printing-plan');
    document.body.classList.remove('printing-list');
    document.querySelectorAll('.printing').forEach(function (c) {
        c.classList.remove('printing');
    });
});
