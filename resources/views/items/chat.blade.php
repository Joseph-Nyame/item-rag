@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Item Assistant</h1>
                <p class="text-gray-600">Get instant answers about your inventory</p>
            </div>
            <a href="{{ route('items.index') }}" class="flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Back to Items
            </a>
        </div>

        <!-- Chat Container -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Chat Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4 flex items-center">
                <div class="bg-white p-2 rounded-full mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-white">Inventory Assistant</h2>
                    <p class="text-blue-100 text-sm">Powered by AI</p>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="chatContainer" class="h-96 overflow-y-auto p-6 bg-gray-50">
                <!-- Welcome Message -->
                <div class="text-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-blue-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">How can I help you today?</h3>
                    <p class="text-gray-500 max-w-md mx-auto">Ask me anything about your inventory items, pricing, or availability.</p>
                </div>
            </div>

            <!-- Input Area -->
            <div class="border-t border-gray-200 p-4 bg-white">
                <form id="chatForm" class="flex items-center space-x-2">
                    @csrf
                    <div class="flex-1 relative">
                        <input type="text" name="question" placeholder="Example: Which items need restocking?" 
                               class="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <button type="button" id="clearInput" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <button type="submit" class="p-3 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-2 px-2">Tip: Try asking "Show me items under $50" or "What's low in stock?"</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chatForm');
    const chatContainer = document.getElementById('chatContainer');
    const inputField = chatForm.querySelector('input[name="question"]');
    const clearButton = document.getElementById('clearInput');
    const conversationHistory = [];
    
    // Clear input field
    clearButton.addEventListener('click', function() {
        inputField.value = '';
        inputField.focus();
    });
    
    // Handle form submission
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const question = inputField.value.trim();
        if (!question) return;
        
        // Add user question to chat
        addMessage('user', question);
        inputField.value = '';
        
        try {
            // Show typing indicator
            const typingId = showTypingIndicator();
            
            const response = await fetch("{{ route('items.chat.submit') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({
                    question: question,
                    history: conversationHistory
                })
            });
            
            // Remove typing indicator
            removeTypingIndicator(typingId);
            
            const data = await response.json();
            
            if (data.success) {
                addMessage('assistant', data.answer);
                conversationHistory.push({role: 'user', content: question});
                conversationHistory.push({role: 'assistant', content: data.answer});
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            addMessage('assistant', "I'm having trouble connecting right now. Please try again later.");
            console.error(error);
        }
    });
    
    // Show typing indicator
    function showTypingIndicator() {
        const id = 'typing-' + Date.now();
        const typingDiv = document.createElement('div');
        typingDiv.id = id;
        typingDiv.className = 'flex justify-start mb-3';
        typingDiv.innerHTML = `
            <div class="bg-gray-200 rounded-lg px-4 py-2">
                <div class="flex space-x-1">
                    <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                </div>
            </div>
        `;
        chatContainer.appendChild(typingDiv);
        chatContainer.scrollTop = chatContainer.scrollHeight;
        return id;
    }
    
    // Remove typing indicator
    function removeTypingIndicator(id) {
        const indicator = document.getElementById(id);
        if (indicator) {
            indicator.remove();
        }
    }
    
    // Add message to chat
    function addMessage(role, content) {
        // Remove welcome message if it exists
        if (chatContainer.querySelector('.text-center')) {
            chatContainer.innerHTML = '';
        }
        
        // Remove any existing typing indicators
        document.querySelectorAll('[id^="typing-"]').forEach(el => el.remove());
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `flex ${role === 'user' ? 'justify-end' : 'justify-start'} mb-4`;
        
        if (role === 'user') {
            messageDiv.innerHTML = `
                <div class="max-w-xs md:max-w-md lg:max-w-lg">
                    <div class="bg-blue-600 text-white rounded-t-2xl rounded-l-2xl px-4 py-3">
                        ${content}
                    </div>
                    <div class="text-xs text-gray-500 text-right mt-1 px-1">
                        ${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                    </div>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="max-w-xs md:max-w-md lg:max-w-lg">
                    <div class="flex items-start">
                        <div class="bg-blue-100 text-blue-800 rounded-full p-2 mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <div class="bg-gray-200 rounded-t-2xl rounded-r-2xl px-4 py-3">
                                ${content}
                            </div>
                            <div class="text-xs text-gray-500 mt-1 px-1">
                                ${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        chatContainer.appendChild(messageDiv);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    
    // Allow Enter key to submit (but Shift+Enter for new line)
    inputField.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });
});
</script>
@endpush
@endsection