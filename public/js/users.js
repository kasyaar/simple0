var Simple0 = {};
Simple0.Model = {};
Simple0.Collection = {};
Simple0.View = {};

Simple0.Model.User = Backbone.Model.extend({
    urlRoot: '/users',
    defaults: {
        'firstName': '',
        'lastName': '',
        'email': ''
    }
});

Simple0.Collection.User = Backbone.Collection.extend({
    model: Simple0.Model.User,
    url: '/users'
});

Simple0.View.UserEntryView = Backbone.View.extend({
    tagName: 'li',
    template: _.template($('#item-template').html()),
    events: {
        'mouseover':  function() { this.$('button').show() },
        'mouseleave': function() { this.$('button').hide()},
        'click .confirm-remove': function() {
            this.$('.modal').modal();
        },
        'click .remove-user': function() {
            var that = this;
            this.model.destroy({
                success: function() {
                    that.$('.modal').modal('hide');
                    that.remove();
                },
                error: function(model, state) {
                    var message = JSON.parse(state.responseText).message;
                    alert(message);
                }
            });
        }
    },
    render: function() {
        $(this.el).html(this.template(this.model.toJSON()));
        return this;
    }
});

Simple0.View.UserEditView = Backbone.View.extend({
    el: $('#users-new'),
    events: {
        'click button[type=submit]': function() {
            $('#user-new-errors').hide();

            this.model.save({
                firstName: this.$('input[name=firstName]').val(),
                lastName: this.$('input[name=lastName]').val(),
                email: this.$('input[name=email]').val()
            }, {
                error: function(model, state) {
                    var message = JSON.parse(state.responseText).message;
                    $('#user-new-errors').html(message).show();
                },
                success: function(model, state) {
                    Backbone.history.navigate('#', true);
                }
            });

            return false;
        },
        'click button[class=btn]': function() {
            Backbone.history.navigate('#', true);
        }
    },
    render: function() {
        $('#user-new-errors').hide();

        if(this.model.isNew()) {
            this.$('input[name=firstName]').val('');
            this.$('input[name=lastName]').val('');
            this.$('input[name=email]').val('');
            this.$('h2').text('Add new user');
            this.$('button[type=submit]').text('Add new user');
        } else {
            this.$('input[name=firstName]').val(this.model.get('firstName'));
            this.$('input[name=lastName]').val(this.model.get('lastName'));
            this.$('input[name=email]').val(this.model.get('email'));
            this.$('h2').text('Edit user');
            this.$('button[type=submit]').text('Save');
        }

        $(this.el).show();
        return this;
    }
});

Simple0.View.UserListView = Backbone.View.extend({
    el: $('#users-widget'),
    render: function() {
        var that = this;

        this.collection.fetch({success: function(users) {
            that.$('.widget-listing').empty();
            that.$('em').html('(' + users.length + ')');
            users.each(function(user) {
                var userEntry = new Simple0.View.UserEntryView({model: user});
                that.$('.widget-listing').append(userEntry.render().el);
            });
        }});
        $(this.el).show();

        return this;
    }
});

Simple0.Workspace = Backbone.Router.extend({
    routes: {
        '': 'home',
        'home': 'home',
        'add-user': 'addUser',
        'edit/:id': 'edit'
    },
    initialize: function() {
        this.content = $('#page-content');
        this.collection = new Simple0.Collection.User();
    },
    home: function() {
        this.content.html((new Simple0.View.UserListView({
            collection: this.collection
        })).render().el);
    },
    addUser: function() {
        this.content.html((new Simple0.View.UserEditView({
            model: new Simple0.Model.User()
        })).render().el);
    },
    edit: function(id) {
        var user = new Simple0.Model.User({id: id}), that = this;
        user.fetch({
            success: function(user) {
                that.content.html((new Simple0.View.UserEditView({
                    model: user
                })).render().el);
            },
            error: function(thing, state) {
                var message = JSON.parse(state.responseText).message;
                alert(message);
            }
        });
    }
});

new Simple0.Workspace();
Backbone.history.start();