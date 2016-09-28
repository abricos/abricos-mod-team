var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.WorkspaceWidget = Y.Base.create('workspaceWidget', SYS.AppWidget, [
        SYS.AppWorkspace
    ], {}, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            defaultPage: {
                value: {
                    component: 'teamList',
                    widget: 'TeamListWidget'
                }
            }
        }
    });

    NS.ws = SYS.AppWorkspace.build('{C#MODNAME}', NS.WorkspaceWidget);

    NS.TeamHeaderWidget = Y.Base.create('TeamHeaderWidget', SYS.AppWidget, [], {
        buildTData: function(){
            var team = this.get('team');
            return {
                id: team.get('id'),
                title: team.get('title'),
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'header'},
            team: {}
        },
        CLICKS: {},
    });

    var TeamAppWorkspacePage = function(p){
        this._isTeamPage = true;
        this.teamid = 0;
        TeamAppWorkspacePage.superclass.constructor.apply(this, arguments);
    };
    Y.extend(TeamAppWorkspacePage, SYS.AppWorkspacePage, {
        init: function(p){
            if (!p._isTeamPage){
                this.teamid = p.component | 0;
                p.component = p.widget;

                var args = [];
                if (p.args.length > 0){ // clone
                    p.widget = p.args[0];
                    for (var i = 1; i < p.args.length; i++){
                        args[args.length] = p.args[i];
                    }
                }
                p.args = args;
            }
            TeamAppWorkspacePage.superclass.init.call(this, p);
        },
    });
    NS.TeamAppWorkspacePage = TeamAppWorkspacePage;

    NS.WorkspaceItemWidget = Y.Base.create('WorkspaceItemWidget', SYS.AppWidget, [
        SYS.AppWorkspace,
        SYS.ContainerWidgetExt,
    ], {
        showWorkspacePage: function(page){
            var AppWorkspacePage = this.get('AppWorkspacePage'),
                page = new AppWorkspacePage(page),
                team = this.get('team');

            if (!team || team.get('id') !== page.teamid){
                this._reloadTeam(page);
            } else {
                this._showWorkspacePage(page);
            }
        },
        _reloadTeam: function(page){
            this.set('waiting', true);
            this.get('appInstance').team(page.teamid, function(err, result){
                this.set('waiting', false);
                this._renderTeam(err ? null : result.team, page);
            }, this);
        },
        _renderTeam: function(team, page){
            this.set('team', team);

            this.cleanWidgets();

            if (!team){
                // TODO: show 404 info
                return;
            }

            var tp = this.template;

            this.addWidget('header', new NS.TeamHeaderWidget({
                srcNode: tp.append('headerWidget', '<div></div>'),
                team: team
            }));

            var pluginList = this.get('appInstance').get('pluginList'),
                mods = pluginList.getModules(team);

            NS.uses(mods, 'teamWSpace', function(){
                this.set('waiting', false);
                this._onLoadPluginComponents(page);
            }, this);
        },
        _onLoadPluginComponents: function(page){
            var tp = this.template,
                team = this.get('team'),
                mods = this.get('appInstance').get('pluginList').getModules(team);

            for (var i = 0, name, MenuWidget; i < mods.length; i++){
                name = mods[i];

                if (!(MenuWidget = Brick.mod[name].TeamWSMenuWidget)){
                    continue;
                }

                this.addWidget('menu-' + name, new MenuWidget({
                    srcNode: tp.append('menuWidget', '<div></div>'),
                    team: team
                }));
            }

            return;

            if (page.isEmpty()){
                page.component = 'teamViewer';
                page.widget = 'TeamViewerWidget';
            }

            this._showWorkspacePage(page);
        },
        onFillWidgetOptions: function(options){
            return Y.mix({
                team: this.get('team')
            }, options);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'team'},
            AppWorkspacePage: {
                value: NS.TeamAppWorkspacePage
            },
            team: NS.ATTRIBUTE.team,
            defaultPage: {
                getter: function(){
                    var team = this.get('team');

                    return {
                        component: 'teamViewer',
                        widget: 'TeamViewerWidget'
                    };
                }
            }
        }
    });

    NS.item = SYS.AppWorkspace.build('{C#MODNAME}', NS.WorkspaceItemWidget);
};
