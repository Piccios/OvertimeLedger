<?php
// Language translations
$translations = [
    'it' => [
        // Page title
        'page_title' => 'Gestore Ore Straordinarie',
        
        // Form labels
        'add_overtime' => 'Aggiungi Ore Straordinarie',
        'required_fields' => '(*) Campi obbligatori',
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
        'english' => 'Inglese',
        
        // Additional keys
        'manage_companies' => 'Gestione Aziende',
        'add_new_company' => 'Aggiungi Nuova Azienda',
        'company_name' => 'Nome Azienda',
        'color' => 'Colore',
        'add' => 'Aggiungi',
        'companies_list' => 'Elenco Aziende',
        'no_companies' => 'Nessuna azienda presente nel sistema.',
        'edit_company' => 'Modifica Azienda',
        'back_to_main' => 'Torna al Menu Principale',
        'confirm_delete_company' => 'Sei sicuro di voler eliminare questa azienda?',
        'company_added' => 'Azienda aggiunta con successo!',
        'company_modified' => 'Azienda modificata con successo!',
        'company_deleted' => 'Azienda eliminata con successo!'
    ],
    'en' => [
        // Page title
        'page_title' => 'Overtime Hours Manager',
        
        // Form labels
        'required_fields' => '(*) Required fields',
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
        'english' => 'English',
        
        // Additional keys
        'manage_companies' => 'Manage Companies',
        'add_new_company' => 'Add New Company',
        'company_name' => 'Company Name',
        'color' => 'Color',
        'add' => 'Add',
        'companies_list' => 'Companies List',
        'no_companies' => 'No companies present in the system.',
        'edit_company' => 'Edit Company',
        'back_to_main' => 'Back to Main Menu',
        'confirm_delete_company' => 'Are you sure you want to delete this company?',
        'company_added' => 'Company added successfully!',
        'company_modified' => 'Company modified successfully!',
        'company_deleted' => 'Company deleted successfully!'
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