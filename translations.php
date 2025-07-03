<?php
// Language translations
$translations = [
    'it' => [
        // Page title
        'page_title' => 'Gestore Ore Straordinarie',
        
        // Form labels
        'add_overtime' => 'Aggiungi Ore Straordinarie',
        'company' => 'Azienda',
        'select_company' => 'Seleziona Azienda',
        'date' => 'Data',
        'hours' => 'Ore',
        'description' => 'Descrizione',
        'optional' => 'Opzionale',
        'save' => 'Salva',
        
        // Current week section
        'current_week' => 'Settimana Corrente',
        'no_overtime_week' => 'Nessuna ora straordinaria registrata questa settimana.',
        'actions' => 'Azioni',
        'edit' => 'Modifica',
        'delete' => 'Elimina',
        'confirm_delete' => 'Sei sicuro?',
        
        // Monthly summary
        'monthly_summary' => 'Riepilogo Mensile',
        'export_excel' => 'Esporta Excel',
        'no_data_month' => 'Nessun dato per questo mese.',
        'total_monthly_hours' => 'Totale Ore Mensili',
        'summary_by_company' => 'Riepilogo per Azienda',
        'of_total' => 'del totale',
        
        // Modal
        'edit_record' => 'Modifica Record',
        'edit_description_optional' => 'Descrizione (Opzionale)',
        'cancel' => 'Annulla',
        'save_changes' => 'Salva Modifiche',
        
        // Toast messages
        'record_added' => 'Record aggiunto con successo!',
        'record_deleted' => 'Record eliminato con successo!',
        'record_edited' => 'Record modificato con successo!',
        
        // Language selector
        'language' => 'Lingua',
        'italian' => 'Italiano',
        'english' => 'Inglese'
    ],
    'en' => [
        // Page title
        'page_title' => 'Overtime Hours Manager',
        
        // Form labels
        'add_overtime' => 'Add Overtime Hours',
        'company' => 'Company',
        'select_company' => 'Select Company',
        'date' => 'Date',
        'hours' => 'Hours',
        'description' => 'Description',
        'optional' => 'Optional',
        'save' => 'Save',
        
        // Current week section
        'current_week' => 'Current Week',
        'no_overtime_week' => 'No overtime hours recorded this week.',
        'actions' => 'Actions',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'confirm_delete' => 'Are you sure?',
        
        // Monthly summary
        'monthly_summary' => 'Monthly Summary',
        'export_excel' => 'Export Excel',
        'no_data_month' => 'No data for this month.',
        'total_monthly_hours' => 'Total Monthly Hours',
        'summary_by_company' => 'Summary by Company',
        'of_total' => 'of total',
        
        // Modal
        'edit_record' => 'Edit Record',
        'edit_description_optional' => 'Description (Optional)',
        'cancel' => 'Cancel',
        'save_changes' => 'Save Changes',
        
        // Toast messages
        'record_added' => 'Record added successfully!',
        'record_deleted' => 'Record deleted successfully!',
        'record_edited' => 'Record edited successfully!',
        
        // Language selector
        'language' => 'Language',
        'italian' => 'Italian',
        'english' => 'English'
    ]
];

// Function to get translation
function t($key, $lang = 'it') {
    global $translations;
    return $translations[$lang][$key] ?? $key;
}

// Get current language from GET parameter, default to Italian
$current_lang = isset($_GET['lang']) && in_array($_GET['lang'], ['it', 'en']) ? $_GET['lang'] : 'it';
?> 