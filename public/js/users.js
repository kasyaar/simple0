$(function(){
    var Simple0 = {};
    Simple0.Model = {};
    Simple0.Collection = {};
    Simple0.View = {};

    Simple0.Model.User = Backbone.Model.extend({
        defaults: {
            'id': '',
            'firstName': '',
            'lastName': '',
            'email': ''
        }
    });

    Simple0.Collection.User = Backbone.Collection.extend({
        model: Simple0.Model.User,
        url: '/users'
    });

    Simple0.View.User = Backbone.View.extend({
        tagName: 'li',
        template: _.template($('#item-template').html()),
        events: {
            'mouseover':  function() { $('button', this.el).show() },
            'mouseleave': function() { $('button', this.el).hide()},
            'click .confirm-remove': function() {
                $('.modal', this.el).modal();
            },
            'click .remove-user': function() {
                $('.modal', this.el).modal('hide');
                this.remove();
            }
        },
        render: function() {
            $(this.el).html(this.template(this.model.toJSON()));
            return this;
        }
    });

    Simple0.View.UserList = Backbone.View.extend({
        initialize: function() {
            _.bindAll(this, 'render');
            this.collection.bind('all', this.render);
            this.collection.fetch();
        },
        render: function() {
            this.collection.each(function(user) {
                var userEntry = new Simple0.View.User({model: user});
                this.$('#users-widget .widget-listing').append(userEntry.render().el);
            });
        }
    });

    var App = new Simple0.View.UserList({
        collection: new Simple0.Collection.User()
    });
});