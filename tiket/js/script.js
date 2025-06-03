// Example function to handle ticket purchase
function buyTicket(ticketId) {
    alert('Ticket purchased: ' + ticketId);
}

function showPurchaseForm(ticketId) {
    document.getElementById('modal_ticket_id').value = ticketId;
    document.getElementById('purchaseModal').style.display = 'block';
}

function closePurchaseForm() {
    document.getElementById('purchaseModal').style.display = 'none';
}
