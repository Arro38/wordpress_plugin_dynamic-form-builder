jQuery(document).ready(function($) {
    $('.dfb-form-container').each(function() {
        const $container = $(this);
        const formId = $container.data('form-id');
        let currentQuestionIndex = 0;
        const $questions = $container.find('.dfb-question');
        const questionsData = {};

        if ($questions.length === 0) {
            console.error('Aucune question trouvée dans le formulaire.');
            return;
        }

        // Charger les données des questions
        $questions.each(function() {
            const $question = $(this);
            const questionIndex = $question.data('question-index');
            const answers = [];
            
            $question.find('.dfb-answer-button').each(function() {
                const $button = $(this);
                const $label = $button.closest('.dfb-answer');
                answers.push({
                    text: $button.text(),
                    action_type: $label.data('action-type'),
                    action_value: $label.data('action-value')
                });
            });

            questionsData[questionIndex] = {
                answers: answers
            };
        });

        // Initialiser l'affichage
        function initializeForm() {
            $questions.hide(); // Masquer toutes les questions

            // Afficher la première question
            const firstQuestionIndex = $questions.first().data('question-index');
            showQuestion(firstQuestionIndex);
        }

        // Afficher une question spécifique
        function showQuestion(index) {
            // Trouver la question avec l'index personnalisé
            const $currentQuestion = $('.dfb-question[data-question-index="' + index + '"]');

            if ($currentQuestion.length > 0) { // Vérifier que l'élément existe
                $questions.hide(); // Masquer toutes les questions
                $currentQuestion.css({ opacity: 0 }).show().animate({ opacity: 1 }, 400); // Animation fluide
            } else {
                console.error('La question avec l\'index ' + index + ' n\'existe pas.');
            }
        }

        // Gérer la réponse sélectionnée
        function handleAnswer($label) {
            const actionType = $label.data('action-type');
            const actionValue = $label.data('action-value');

            // Gérer l'action
            if (actionType === 'next_question') {
                showQuestion(actionValue); // Utiliser l'index personnalisé
            } else if (actionType === 'redirect') {
                // Cacher tous les choix
                $container.find('.dfb-answers').hide();

                // Afficher un message de redirection
                const $redirectMessage = $('<div class="dfb-redirect-message">Redirection en cours...</div>');
                $container.append($redirectMessage);

                // Rediriger après un court délai
                setTimeout(() => {
                    window.location.href = actionValue;
                }, 1500); // Délai de 1,5 seconde avant la redirection
            }
        }

        // Gérer les événements
        $container.on('click', '.dfb-answer-button', function() {
            const $button = $(this);
            const $label = $button.closest('.dfb-answer');
            handleAnswer($label);
        });

        // Initialiser le formulaire
        initializeForm();
    });
});