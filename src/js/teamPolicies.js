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

    NS.TeamPoliciesWidget = Y.Base.create('TeamPoliciesWidget', SYS.AppWidget, [
        NS.PluginWidgetExt
    ], {
        onInitAppWidget: function(err, appInstance){
            var team = this.get('team'),
                teamid = team.get('id');

            this.set('waiting', true);
            appInstance.policies(teamid, function(err, result){
                if (err){
                    return;
                }
                this.set('waiting', false);
                this.set('policyList', result.policyList);
                this.set('actionList', result.actionList);
                this.set('roleList', result.roleList);
                this.renderPolicyList();
            }, this);
        },
        _isSetAction: function(policyid, action){
            var mask = 0;
            this.get('roleList').some(function(role){
                if (role.get('policyid') === policyid
                    && action.get('group') === role.get('group')){
                    mask = role.get('mask');
                    return true;
                }
            }, this);
            return mask & action.get('code');
        },
        renderPolicyList: function(){
            var tp = this.template,
                ownerApp = this.get('ownerApp'),
                lst = "",
                activePolicyId = this.get('activePolicyId');


            this.get('policyList').each(function(policy){
                if (activePolicyId === 0){
                    activePolicyId = policy.get('id');
                    this.set('activePolicyId', activePolicyId)
                }
                lst += tp.replace('policyRow', {
                    activeClass: policy.get('id') === activePolicyId ? 'active' : '',
                    id: policy.get('id'),
                    title: policy.get('title')
                });
            }, this);

            tp.setHTML('policyList', lst);

            var lstAction = "";

            this.get('actionList').each(function(action){
                lstAction += tp.replace('actionRow', {
                    id: action.get('id'),
                    title: action.get('title'),
                    checked: this._isSetAction(activePolicyId, action) ? 'checked' : ''
                });
            }, this);

            tp.setHTML('actionList', tp.replace('actionTable', {
                rows: lstAction
            }));
        },
        onClick: function(e){
            var id = e.defineTarget.getData('id') | 0;
            switch (e.dataClick) {
                case 'policyItem':
                    this.set('activePolicyId', id);
                    this.renderPolicyList();
                    return true;
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,policyRow,actionTable,actionRow'},
            ownerApp: {
                readOnly: true,
                getter: function(){
                    var team = this.get('team');
                    return Brick.mod[team.get('module')].appInstance;
                }
            },
            activePolicyId: {value: 0},
            policyList: {},
            actionList: {},
            roleList: {},
        }
    });
};
