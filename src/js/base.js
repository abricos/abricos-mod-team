var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['application.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    NS.uses = function(modules, component, callback, context){
        if (!modules || modules.length === 0){
            return callback.call(context, null);
        }
        var module = modules.pop();

        Brick.use(module, component, function(err, ns){
            NS.uses(modules, component, callback, context);
        });
    };

    NS.initApps = function(stack, callback, context){
        if (Y.Lang.isString(stack)){
            stack = [stack];
        }
        if (stack.length === 0){
            return callback.call(context || this);
        }
        var appName = stack.pop();

        Brick.use(appName, 'lib', function(err, ns){
            if (err){
                return NS.initApps(stack, callback, context);
            }
            ns.initApp({
                initCallback: function(err, appInstance){
                    return NS.initApps(stack, callback, context);
                }
            });
        });
    };

    var PluginWidgetExt = function(){
    };
    PluginWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        team: NS.ATTRIBUTE.team,
    };
    PluginWidgetExt.prototype = {
        buildTData: function(){
            return {
                teamid: this.get('team').get('id')
            };
        },
    };
    NS.PluginWidgetExt = PluginWidgetExt;

    var PluginWSMenuWidgetExt = function(){
    };
    PluginWSMenuWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        team: NS.ATTRIBUTE.team,
        page: {}
    };
    PluginWSMenuWidgetExt.prototype = {
        buildTData: function(){
            return {
                teamid: this.get('team').get('id')
            };
        },
        onInitAppWidget: function(err, appInstance){
            this.menuItemUpdate();
        },
        menuItemUpdate: function(){
            var page = this.get('page'),
                bbox = this.get('boundingBox'),
                names, name, i, isActive;

            bbox.all('[data-menuItem]').each(function(node){
                names = node.getData('menuItem').split(',');

                isActive = false;
                for (i = 0; i < names.length; i++){
                    name = Y.Lang.trim(names[i]);
                    if (page.widget === name){
                        isActive = true;
                        break;
                    }
                }

                if (isActive){
                    node.addClass('active');
                } else {
                    node.removeClass('active');
                }
            }, this);
        }

    };
    NS.PluginWSMenuWidgetExt = PluginWSMenuWidgetExt;
};