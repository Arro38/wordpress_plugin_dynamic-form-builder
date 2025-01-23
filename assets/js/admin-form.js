jQuery(document).ready(function($) {

    // Template pour une nouvelle question
    function getQuestionTemplate(index) {
        return `
            <div class="question-item" data-question-id="${index}">
                <p>
                    <label>Question:</label>
                    <input type="text" name="dfb_questions[${index}][question]" value="" class="question-text" />
                </p>
                <div class="answers-container">
                    <h4>Réponses possibles</h4>
                    <div class="answers-list">
                    </div>
                    <button type="button" class="button add-answer">Ajouter une réponse</button>
                </div>
                <button type="button" class="button remove-question">Supprimer la question</button>
            </div>
        `;
    }

    // Template pour une nouvelle réponse
    function getAnswerTemplate(questionIndex, answerIndex) {
        return `
            <div class="answer-item">
                <input type="text" 
                       name="dfb_questions[${questionIndex}][answers][${answerIndex}][text]" 
                       placeholder="Texte de la réponse" 
                       class="answer-text" />
                <select name="dfb_questions[${questionIndex}][answers][${answerIndex}][action_type]" 
                        class="answer-action-type">
                    <option value="next_question">Aller à une question</option>
                    <option value="redirect">Rediriger vers un lien</option>
                </select>
                <div class="action-value-container">
                    <select name="dfb_questions[${questionIndex}][answers][${answerIndex}][action_value_question]" 
                        class="question-select action-value next-question-field">
                    <option value="">Sélectionnez une question</option>
                </select>
                <input type="url" 
                    name="dfb_questions[${questionIndex}][answers][${answerIndex}][action_value_url]" 
                    placeholder="Entrez l'URL de redirection" 
                    class="action-value redirect-field" 
                    style="display: none;" />
                </div>
                <button type="button" class="button remove-answer">Supprimer la réponse</button>
            </div>
        `;
    }

    // Mettre à jour les sélecteurs de questions uniquement pour un élément spécifique
    function updateQuestionSelectorsForElement($element) {
        const questions = [];
        $('.question-item').each(function() {
            const questionId = $(this).data('question-id');
            const questionText = $(this).find('.question-text').val();
            if (questionText) {
                questions.push({ id: questionId, text: questionText });
            }
        });

        const questionId = $element.closest('.question-item').data('question-id');
        let options = '<option value="">Sélectionnez une question</option>' +
            questions
                .filter(q => q.id !== questionId)
                .map(q => `<option value="${q.id}">${q.text}</option>`)
                .join('');
        
        $element.find('.question-select').html(options);
    }

    // Ajouter une nouvelle question
    $('#add-question').on('click', function() {
        const $container = $('.dfb-questions-list');
        const index = new Date().getTime();
        const $newQuestion = $(getQuestionTemplate(index));
        $container.append($newQuestion);
        updateQuestionSelectorsForElement($newQuestion);
    });

    // Ajouter une nouvelle réponse
    $(document).on('click', '.add-answer', function() {
        const $questionItem = $(this).closest('.question-item');
        const questionIndex = $questionItem.data('question-id');
        const $answersList = $questionItem.find('.answers-list');
        const answerIndex = new Date().getTime();
        const $newAnswer = $(getAnswerTemplate(questionIndex, answerIndex));
        updateQuestionSelectorsForElement($newAnswer);
        $answersList.append($newAnswer);
    });

    // Supprimer une question
    $(document).on('click', '.remove-question', function() {
        $(this).closest('.question-item').remove();
    });

    // Supprimer une réponse
    $(document).on('click', '.remove-answer', function() {
        $(this).closest('.answer-item').remove();
    });

    // Gérer le changement de type d'action
    $(document).on('change', '.answer-action-type', function() {
        const $container = $(this).next('.action-value-container');
        const actionType = $(this).val();
        
        if (actionType === 'next_question') {
            $container.find('.next-question-field').show();
            $container.find('.redirect-field').hide();
        } else {
            $container.find('.next-question-field').hide();
            $container.find('.redirect-field').show();
        }
    });

    // Validation avant la soumission du formulaire
    $('#post').on('submit', function(e) {
        $('.question-item').each(function() {
            const $question = $(this);
            const questionText = $question.find('.question-text').val();
            
            if (!questionText) {
                $question.remove();
                return;
            }
    
            $question.find('.answer-item').each(function() {
                const $answer = $(this);
                const answerText = $answer.find('.answer-text').val();
                const actionType = $answer.find('.answer-action-type').val();
                const actionValue = actionType === 'next_question' 
                    ? $answer.find('.action-value.next-question-field').val() 
                    : $answer.find('.action-value.redirect-field').val();
                
                // Ne supprimez pas la réponse si elle n'a pas de texte ou de valeur d'action
                if (!answerText || !actionValue) {
                    return;
                }
            });
        });
    });

    // Initialisation
});
